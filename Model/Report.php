<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model;

use Magento\Framework\Model\AbstractModel;
use Merlin\SalesInsights\Model\ResourceModel\Report as ReportResource;

class Report extends AbstractModel
{
    protected function _construct(): void
    {
        $this->_init(ReportResource::class);
    }
}
