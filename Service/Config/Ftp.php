<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Ftp
{
    private const FTP_HOST_PATH = 'followthesun/export_configuration/ftp/host';
    private const FTP_PORT_PATH = 'followthesun/export_configuration/ftp/port';
    private const FTP_USERNAME_PATH = 'followthesun/export_configuration/ftp/username';
    private const FTP_PASSWORD_PATH = 'followthesun/export_configuration/ftp/password';

    private const DEFAULT_FTP_PATH = 'Import';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private EncryptorInterface $encryptor
    ) {
    }

    public function getFtpHost(): string
    {
        return (string) $this->scopeConfig->getValue(self::FTP_HOST_PATH);
    }

    public function getFtpPort(): int
    {
        return (int) $this->scopeConfig->getValue(self::FTP_PORT_PATH);
    }

    public function getFtpUsername(): string
    {
        return (string) $this->scopeConfig->getValue(self::FTP_USERNAME_PATH);
    }

    public function getFtpPassword(): string
    {
        return $this->encryptor->decrypt($this->scopeConfig->getValue(self::FTP_PASSWORD_PATH) ?? '');
    }
}
