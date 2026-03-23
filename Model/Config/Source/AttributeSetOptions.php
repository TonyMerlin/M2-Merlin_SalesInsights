<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model\Config\Source;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;

class AttributeSetOptions implements OptionSourceInterface
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
            ->from($table, ['value' => 'attribute_set_name', 'label' => 'attribute_set_name'])
            ->where('attribute_set_name <> ?', '')
            ->group('attribute_set_name')
            ->order('attribute_set_name ASC');

        $rows = $connection->fetchAll($select);

        array_unshift($rows, [
            'value' => '',
            'label' => __('-- Please Select --')
        ]);

        return $rows;
    }
}
