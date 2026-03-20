<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Cron;

use Merlin\SalesInsights\Api\AggregatorInterface;
use Merlin\SalesInsights\Model\Config;
use Psr\Log\LoggerInterface;

class RebuildAggregates
{
    public function __construct(
        private readonly AggregatorInterface $aggregator,
        private readonly Config $config,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        try {
            $from = date('Y-m-d', strtotime('-400 days'));
            $to = date('Y-m-d');
            $this->aggregator->rebuild($from, $to);
        } catch (\Throwable $e) {
            $this->logger->critical('[Merlin_SalesInsights] Cron rebuild failed: ' . $e->getMessage(), ['exception' => $e]);
        }
    }
}
