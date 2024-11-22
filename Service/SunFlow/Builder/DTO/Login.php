<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\SunflowSDK\Contact\Model\SystemCRM360DataContactConnexion;
use FollowTheSun\SunflowSDK\Contact\Model\SystemCRM360DataContactIdentifier;
use Magento\Customer\Api\Data\CustomerInterface;

class Login
{
    public function __construct(
        private SunFlow $sunflowConfig
    ) {
    }

    public function create(CustomerInterface $customer): SystemCRM360DataContactConnexion
    {
        return (new SystemCRM360DataContactConnexion())->setIdentifiers($this->buildIdentifiers($customer));
    }

    public function buildIdentifiers(CustomerInterface $customer): array
    {
        return [
            (new SystemCRM360DataContactIdentifier())
                ->setIdentifier($customer->getId())
                ->setSourceId($this->sunflowConfig->getSourceId())
        ];
    }
}
