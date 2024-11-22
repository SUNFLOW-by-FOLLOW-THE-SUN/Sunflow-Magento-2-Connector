<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Config\Backend;

use LogicException;
use Magento\Framework\App\Config\Value;

class CronExpression extends Value
{
    public const DEFAULT_HOUR_EXPORT = 2;

    public function beforeSave()
    {
        $time = $this->getValue();
        if ($time === null) {
            $time = self::DEFAULT_HOUR_EXPORT;
        }

        $time = (int) $time;
        if ($time < 0 || $time > 23) {
            throw new LogicException(__('Export hour must be in 0-23 range.')->render());
        }

        $this->setValue(sprintf('0 %s * * *', $time));

        return parent::beforeSave();
    }

    // phpcs:disable Squiz.NamingConventions.ValidFunctionName.PublicUnderscore
    protected function _afterLoad(): void
    {
        preg_match('/0 ([1-9]|1[0-9]|2[0-3]) \* \* \*/', $this->getValue(), $matches);

        $this->setValue($matches[1] ?? (string) self::DEFAULT_HOUR_EXPORT);
    }
}
