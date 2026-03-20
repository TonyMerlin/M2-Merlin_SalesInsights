<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model\ResourceModel\Report;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Merlin\SalesInsights\Model\Report as ReportModel;
use Merlin\SalesInsights\Model\ResourceModel\Report as ReportResource;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'entity_id';

    protected function _construct(): void
    {
        $this->_init(ReportModel::class, ReportResource::class);
    }
}
