<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service;

use FollowTheSun\Connector\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Debug
{
    private const DEBUG_ENABLED_PATH = 'followthesun/debug_configuration/enabled';

    private ?bool $isDebugEnabled = null;

    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private Logger $logger
    ) {
    }

    public function isDebugEnabled(): bool
    {
        if ($this->isDebugEnabled === null) {
            $this->isDebugEnabled = $this->scopeConfig->isSetFlag(self::DEBUG_ENABLED_PATH, 'stores');
        }

        return $this->isDebugEnabled;
    }

    public function debug(string $message, array $context = []): void
    {
        if ($this->isDebugEnabled()) {
            $this->logger->debug($message, $context);
        }
    }
}
