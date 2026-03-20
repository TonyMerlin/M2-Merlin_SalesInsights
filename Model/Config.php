<?php
declare(strict_types=1);

namespace Merlin\SalesInsights\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'merlin_salesinsights/general/enabled';
    private const XML_PATH_MANUFACTURER_ATTRIBUTE_CODE = 'merlin_salesinsights/general/manufacturer_attribute_code';
    private const XML_PATH_INCLUDED_ORDER_STATUSES = 'merlin_salesinsights/general/included_order_statuses';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getManufacturerAttributeCode(?int $storeId = null): string
    {
        $value = (string)$this->scopeConfig->getValue(self::XML_PATH_MANUFACTURER_ATTRIBUTE_CODE, ScopeInterface::SCOPE_STORE, $storeId);
        return $value !== '' ? trim($value) : 'manufacturer';
    }

    public function getIncludedOrderStatuses(?int $storeId = null): array
    {
        $raw = (string)$this->scopeConfig->getValue(self::XML_PATH_INCLUDED_ORDER_STATUSES, ScopeInterface::SCOPE_STORE, $storeId);
        if ($raw === '') {
            $raw = 'processing,complete,delivery_confirmed,finance_accepted,delivery_confirmed_old,order_complete';
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
