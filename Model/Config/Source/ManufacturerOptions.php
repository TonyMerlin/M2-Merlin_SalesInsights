<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model\Config\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;

class ManufacturerOptions implements OptionSourceInterface
{
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    public function toOptionArray(): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('merlin_salesinsights_aggregate');

        $select = $connection->select()
            ->from($table, ['value' => 'manufacturer_label', 'label' => 'manufacturer_label'])
            ->where('manufacturer_label <> ?', '')
            ->group('manufacturer_label')
            ->order('manufacturer_label ASC');

        $rows = $connection->fetchAll($select);

        array_unshift($rows, [
            'value' => '',
            'label' => __('-- Please Select --')
        ]);

        return $rows;
    }
}
