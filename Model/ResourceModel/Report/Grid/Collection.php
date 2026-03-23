<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model\ResourceModel\Report\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Psr\Log\LoggerInterface;

class Collection extends SearchResult
{
    public function __construct(
        EntityFactoryInterface $entityFactory,
        LoggerInterface $logger,
        FetchStrategyInterface $fetchStrategy,
        ManagerInterface $eventManager,
        $mainTable = 'merlin_salesinsights_aggregate',
        $resourceModel = \Merlin\SalesInsights\Model\ResourceModel\Report::class,
        $identifierName = 'entity_id',
        $connectionName = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $mainTable,
            $resourceModel,
            $identifierName,
            $connectionName
        );
    }

    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addFieldToFilter('period_type', 'daily');
        return $this;
    }
}
