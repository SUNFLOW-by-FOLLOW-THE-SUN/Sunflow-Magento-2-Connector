<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Mode implements OptionSourceInterface
{
    public const DELTA_MODE = 0;
    public const FULL_MODE = 1;

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::DELTA_MODE,
                'label' => __('Delta')
            ],
            [
                'value' => self::FULL_MODE,
                'label' => __('Full')
            ]
        ];
    }
}
