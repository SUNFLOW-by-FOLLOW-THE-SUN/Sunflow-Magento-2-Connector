<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder;

use FollowTheSun\Connector\Service\Config\SunFlow;

class QueryParameters
{
    public function __construct(
        private SunFlow $sunflowConfig
    ) {
    }

    public function create(): array
    {
        return [
            'SourceId'             => $this->sunflowConfig->getSourceId(),
            'ZoneId'               => $this->sunflowConfig->getZoneId(),
            'BrandId'              => $this->sunflowConfig->getBrandId(),
            'ContactAddressTypeId' => $this->sunflowConfig->getAddressTypeId()
        ];
    }

    public function getPlatform(): string
    {
        return 'Magento';
    }
}
