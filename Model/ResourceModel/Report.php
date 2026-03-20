<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Report extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('merlin_salesinsights_aggregate', 'entity_id');
    }
}
