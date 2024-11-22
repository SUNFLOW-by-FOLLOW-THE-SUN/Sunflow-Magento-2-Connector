<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Config;

use FollowTheSun\Connector\Model\Config\Source\Mode;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Export
{
    private const EXPORT_ENTITY_PRODUCT_PATH = 'followthesun/export_configuration/entity_export/product';
    private const EXPORT_ENTITY_PRODUCT_STORE_PATH = 'followthesun/export_configuration/entity_export/product_store';
    private const EXPORT_ENTITY_CATEGORY_PATH = 'followthesun/export_configuration/entity_export/category';
    private const EXPORT_ENTITY_CUSTOMER_PATH = 'followthesun/export_configuration/entity_export/customer';
    private const EXPORT_ENTITY_ORDER_PATH = 'followthesun/export_configuration/entity_export/order';
    private const EXPORT_CLEAN_FILES_PATH = 'followthesun/export_configuration/clean_files';
    private const EXPORT_MODE_PATH = 'followthesun/export_configuration/mode';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function isDeltaModeEnabled(): bool
    {
        return ((int) $this->scopeConfig->getValue(self::EXPORT_MODE_PATH)) === Mode::DELTA_MODE;
    }

    public function isExportEntityProductEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::EXPORT_ENTITY_PRODUCT_PATH);
    }

    public function isExportEntityProductStoreEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::EXPORT_ENTITY_PRODUCT_STORE_PATH);
    }

    public function isExportEntityProductCategoryEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::EXPORT_ENTITY_CATEGORY_PATH);
    }

    public function isExportEntityCustomerEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::EXPORT_ENTITY_CUSTOMER_PATH);
    }

    public function isExportEntityOrderEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::EXPORT_ENTITY_ORDER_PATH);
    }

    public function isCleanFilesEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::EXPORT_CLEAN_FILES_PATH);
    }
}
