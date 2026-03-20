<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Api;

interface AggregatorInterface
{
    public function rebuild(?string $fromDate = null, ?string $toDate = null): void;
}
