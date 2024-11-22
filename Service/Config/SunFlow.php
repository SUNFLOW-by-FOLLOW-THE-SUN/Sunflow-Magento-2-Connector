<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class SunFlow
{
    private const SUNFLOW_API_KEY_PATH = 'followthesun/sunflow_configuration/api_key';
    private const SUNFLOW_SOURCE_ID_PATH = 'followthesun/sunflow_configuration/source_id';
    private const SUNFLOW_ZONE_ID_PATH = 'followthesun/sunflow_configuration/zone_id';
    private const SUNFLOW_BRAND_ID_PATH = 'followthesun/sunflow_configuration/brand_id';
    private const SUNFLOW_ADDRESS_TYPE_ID_PATH = 'followthesun/sunflow_configuration/address_type_id';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private EncryptorInterface $encryptor
    ) {
    }

    public function getApiKey(): string
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::SUNFLOW_API_KEY_PATH) ?? '');
    }

    public function getSourceId(): int
    {
        return (int) $this->scopeConfig->getValue(self::SUNFLOW_SOURCE_ID_PATH);
    }

    public function getZoneId(): int
    {
        return (int) $this->scopeConfig->getValue(self::SUNFLOW_ZONE_ID_PATH);
    }

    public function getBrandId(): int
    {
        return (int) $this->scopeConfig->getValue(self::SUNFLOW_BRAND_ID_PATH);
    }

    public function getAddressTypeId(): int
    {
        return (int) $this->scopeConfig->getValue(self::SUNFLOW_ADDRESS_TYPE_ID_PATH);
    }
}
