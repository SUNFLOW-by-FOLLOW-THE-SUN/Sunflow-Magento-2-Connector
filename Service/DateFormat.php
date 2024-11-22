<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service;

use DateTime;
use DateTimeZone;
use Exception;
use Magento\Config\Model\Config\Backend\Admin\Custom;
use Magento\Framework\App\Config\ScopeConfigInterface;

class DateFormat
{
    private const DEFAULT_TIMEZONE = 'UTC';

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private Debug $debug
    ) {
    }

    public function getDateFormatted(?DateTime $dateTime): string
    {
        return (string) $dateTime?->format('d/m/Y');
    }

    public function getDateTimeFormatted(?DateTime $dateTime): string
    {
        return (string) $dateTime?->format('d/m/Y H:i:s');
    }

    public function createDateTime(
        string $value,
        bool $withTimezone = false,
        ?string $format = 'Y-m-d H:i:s',
    ): ?DateTime {
        $dateTime = DateTime::createFromFormat($format, $value);

        if (!$dateTime || strpos($value, '0000') !== false) {
            return null;
        }

        if (!$withTimezone) {
            return $dateTime;
        }

        try {
            $dateTime = $dateTime->setTimezone(new DateTimeZone($this->getTimezone()));
        } catch (Exception $e) {
            $this->debug->debug(sprintf('Failed to set timezone : %s', $e->getMessage()));
        }

        return $dateTime;
    }

    public function createNowDateTime(bool $withTimezone = false): DateTime
    {
        $now = new DateTime();
        if (!$withTimezone) {
            return $now;
        }

        try {
            $now = $now->setTimezone(new DateTimeZone($this->getTimezone()));
        } catch (Exception $e) {
            $this->debug->debug(sprintf('Failed to set timezone : %s', $e->getMessage()));
        }

        return $now;
    }

    protected function getTimezone(): string
    {
        return $this->scopeConfig->getValue(Custom::XML_PATH_GENERAL_LOCALE_TIMEZONE, 'stores') ??
            self::DEFAULT_TIMEZONE;
    }
}
