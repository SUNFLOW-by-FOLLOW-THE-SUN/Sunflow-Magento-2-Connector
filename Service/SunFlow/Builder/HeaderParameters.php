<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder;

use FollowTheSun\Connector\Service\Config\SunFlow;

class HeaderParameters
{
    public function __construct(
        private SunFlow $sunflowConfig
    ) {
    }

    public function create(): array
    {
        return [
            'X-Crm360Api-ApiKey' => $this->sunflowConfig->getApiKey()
        ];
    }
}
