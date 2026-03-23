<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Controller\Adminhtml\Report;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class Totals extends Action
{
    public const ADMIN_RESOURCE = 'Merlin_SalesInsights::report';

    public function __construct(
        Action\Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ResourceConnection $resource
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('merlin_salesinsights_aggregate');

        $filters = (array)$this->getRequest()->getParam('filters', []);

        $select = $connection->select()
            ->from(
                ['main_table' => $table],
                [
                    'order_count' => 'SUM(main_table.order_count)',
                    'qty_ordered' => 'SUM(main_table.qty_ordered)',
                    'base_row_total' => 'SUM(main_table.base_row_total)',
                    'base_discount_amount' => 'SUM(main_table.base_discount_amount)',
                    'base_tax_amount' => 'SUM(main_table.base_tax_amount)',
                    'base_row_total_incl_tax' => 'SUM(main_table.base_row_total_incl_tax)',
                    'base_net_sales' => 'SUM(main_table.base_net_sales)',
                ]
            )
            ->where('main_table.period_type = ?', 'daily');

        $dateFilter = $filters['period_start'] ?? [];
        if (is_array($dateFilter)) {
            if (!empty($dateFilter['from'])) {
                $select->where('main_table.period_start >= ?', $dateFilter['from']);
            }
            if (!empty($dateFilter['to'])) {
                $select->where('main_table.period_start <= ?', $dateFilter['to']);
            }
        }

        $manufacturer = $filters['manufacturer_label'] ?? '';
        if (is_scalar($manufacturer) && $manufacturer !== '') {
            $select->where('main_table.manufacturer_label = ?', (string)$manufacturer);
        }

        $attributeSet = $filters['attribute_set_name'] ?? '';
        if (is_scalar($attributeSet) && $attributeSet !== '') {
            $select->where('main_table.attribute_set_name = ?', (string)$attributeSet);
        }

        $storeFilter = $filters['store_id'] ?? null;
        if (is_array($storeFilter)) {
            if (($storeFilter['from'] ?? '') !== '') {
                $select->where('main_table.store_id >= ?', (int)$storeFilter['from']);
            }
            if (($storeFilter['to'] ?? '') !== '') {
                $select->where('main_table.store_id <= ?', (int)$storeFilter['to']);
            }
        } elseif (is_scalar($storeFilter) && $storeFilter !== '') {
            $select->where('main_table.store_id = ?', (int)$storeFilter);
        }

        $row = $connection->fetchRow($select) ?: [];

        return $result->setData([
            'success' => true,
            'totals' => [
                'order_count' => (int)($row['order_count'] ?? 0),
                'qty_ordered' => (float)($row['qty_ordered'] ?? 0),
                'base_row_total' => (float)($row['base_row_total'] ?? 0),
                'base_discount_amount' => (float)($row['base_discount_amount'] ?? 0),
                'base_tax_amount' => (float)($row['base_tax_amount'] ?? 0),
                'base_row_total_incl_tax' => (float)($row['base_row_total_incl_tax'] ?? 0),
                'base_net_sales' => (float)($row['base_net_sales'] ?? 0),
            ]
        ]);
    }
}
