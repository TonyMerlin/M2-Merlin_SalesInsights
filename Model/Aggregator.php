<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Merlin\SalesInsights\Api\AggregatorInterface;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

class Aggregator implements AggregatorInterface
{
    private const TABLE_AGGREGATE = 'merlin_salesinsights_aggregate';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function rebuild(?string $fromDate = null, ?string $toDate = null): void
    {
        $connection = $this->resource->getConnection();

        $aggregateTable = $this->resource->getTableName(self::TABLE_AGGREGATE);
        $salesOrderTable = $this->resource->getTableName('sales_order');
        $salesOrderItemTable = $this->resource->getTableName('sales_order_item');
        $productEntityTable = $this->resource->getTableName('catalog_product_entity');
        $eavAttributeTable = $this->resource->getTableName('eav_attribute');
        $eavEntityTypeTable = $this->resource->getTableName('eav_entity_type');
        $catalogProductEntityIntTable = $this->resource->getTableName('catalog_product_entity_int');
        $eavAttributeOptionValueTable = $this->resource->getTableName('eav_attribute_option_value');
        $attributeSetTable = $this->resource->getTableName('eav_attribute_set');

        $manufacturerCode = $this->config->getManufacturerAttributeCode();
        $includedStatuses = $this->config->getIncludedOrderStatuses();

        if ($manufacturerCode === '') {
            throw new LocalizedException(__('Manufacturer attribute code is empty.'));
        }

        if ($includedStatuses === []) {
            throw new LocalizedException(__('Included order statuses are empty.'));
        }

        $fromDate = $fromDate ?: '2000-01-01';
        $toDate = $toDate ?: date('Y-m-d');

        $manufacturerAttributeId = (int)$connection->fetchOne(
            $connection->select()
                ->from(['ea' => $eavAttributeTable], ['attribute_id'])
                ->joinInner(
                    ['et' => $eavEntityTypeTable],
                    'et.entity_type_id = ea.entity_type_id AND et.entity_type_code = "catalog_product"',
                    []
                )
                ->where('ea.attribute_code = ?', $manufacturerCode)
                ->limit(1)
        );

        if (!$manufacturerAttributeId) {
            throw new LocalizedException(__('Could not find catalog_product attribute "%1".', $manufacturerCode));
        }

        $connection->beginTransaction();
        try {
            $connection->delete(
                $aggregateTable,
                [
                    'period_start >= ?' => $fromDate,
                    'period_start <= ?' => $toDate,
                ]
            );

            $periodType = 'daily';
            $periodStartExpr = 'DATE(so.created_at)';
            $periodEndExpr = 'DATE(so.created_at)';

            $select = $connection->select()
                ->from(['soi' => $salesOrderItemTable], [])
                ->joinInner(
                    ['so' => $salesOrderTable],
                    'so.entity_id = soi.order_id',
                    []
                )
                ->joinLeft(
                    ['cpe' => $productEntityTable],
                    'cpe.entity_id = soi.product_id',
                    []
                )
                ->joinLeft(
                    ['manufacturer_int' => $catalogProductEntityIntTable],
                    'manufacturer_int.entity_id = cpe.entity_id'
                    . ' AND manufacturer_int.attribute_id = ' . (int)$manufacturerAttributeId
                    . ' AND manufacturer_int.store_id = 0',
                    []
                )
                ->joinLeft(
                    ['manufacturer_opt_val' => $eavAttributeOptionValueTable],
                    'manufacturer_opt_val.option_id = manufacturer_int.value AND manufacturer_opt_val.store_id = 0',
                    []
                )
                ->joinLeft(
                    ['aset' => $attributeSetTable],
                    'aset.attribute_set_id = cpe.attribute_set_id',
                    []
                )
                ->columns([
                    'period_type' => new Zend_Db_Expr($connection->quote($periodType)),
                    'period_start' => new Zend_Db_Expr($periodStartExpr),
                    'period_end' => new Zend_Db_Expr($periodEndExpr),
                    'store_id' => 'so.store_id',
                    'manufacturer_option_id' => new Zend_Db_Expr('COALESCE(manufacturer_int.value, 0)'),
                    'manufacturer_label' => new Zend_Db_Expr('COALESCE(manufacturer_opt_val.value, "Unknown")'),
                    'attribute_set_id' => new Zend_Db_Expr('COALESCE(cpe.attribute_set_id, 0)'),
                    'attribute_set_name' => new Zend_Db_Expr('COALESCE(aset.attribute_set_name, "Unknown")'),
                    'order_count' => new Zend_Db_Expr('COUNT(DISTINCT so.entity_id)'),
                    'qty_ordered' => new Zend_Db_Expr('SUM(COALESCE(soi.qty_ordered, 0))'),
                    'base_row_total' => new Zend_Db_Expr('SUM(COALESCE(soi.base_row_total, 0))'),
                    'base_discount_amount' => new Zend_Db_Expr('SUM(COALESCE(soi.base_discount_amount, 0))'),
                    'base_tax_amount' => new Zend_Db_Expr('SUM(COALESCE(soi.base_tax_amount, 0))'),
                    'base_row_total_incl_tax' => new Zend_Db_Expr('SUM(COALESCE(soi.base_row_total_incl_tax, 0))'),
                    'base_net_sales' => new Zend_Db_Expr('SUM(COALESCE(soi.base_row_total, 0) - COALESCE(soi.base_discount_amount, 0))')
                ])
                ->where('so.created_at >= ?', $fromDate . ' 00:00:00')
                ->where('so.created_at <= ?', $toDate . ' 23:59:59')
                ->where('so.status IN (?)', $includedStatuses)
                ->where('so.state <> ?', 'canceled')
                ->where('soi.parent_item_id IS NULL')
                ->where('soi.product_id IS NOT NULL')
                ->group([
                    new Zend_Db_Expr($periodStartExpr),
                    new Zend_Db_Expr($periodEndExpr),
                    'so.store_id',
                    new Zend_Db_Expr('COALESCE(manufacturer_int.value, 0)'),
                    new Zend_Db_Expr('COALESCE(manufacturer_opt_val.value, "Unknown")'),
                    new Zend_Db_Expr('COALESCE(cpe.attribute_set_id, 0)'),
                    new Zend_Db_Expr('COALESCE(aset.attribute_set_name, "Unknown")')
                ]);

            $insertSql = $select->insertFromSelect(
                $aggregateTable,
                [
                    'period_type',
                    'period_start',
                    'period_end',
                    'store_id',
                    'manufacturer_option_id',
                    'manufacturer_label',
                    'attribute_set_id',
                    'attribute_set_name',
                    'order_count',
                    'qty_ordered',
                    'base_row_total',
                    'base_discount_amount',
                    'base_tax_amount',
                    'base_row_total_incl_tax',
                    'base_net_sales'
                ]
            );

            $connection->query($insertSql);

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            $this->logger->critical(
                '[Merlin_SalesInsights] Aggregate rebuild failed: ' . $e->getMessage(),
                ['exception' => $e]
            );
            throw $e;
        }
    }
}
