<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Events\Customer;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Builder\DTO\Login as LoginBuilder;
use FollowTheSun\Connector\Service\SunFlow\Builder\HeaderParameters;
use FollowTheSun\Connector\Service\SunFlow\Builder\QueryParameters;
use FollowTheSun\SunflowSDK\Contact\Client;
use Magento\Customer\Api\Data\CustomerInterface;

class Login
{
    public function __construct(
        private HeaderParameters $headerParametersBuilder,
        private QueryParameters $queryParametersBuilder,
        private LoginBuilder $loginBuilder,
        private Debug $debug
    ) {
    }

    public function send(CustomerInterface $customer): void
    {
        $this->debug->debug(sprintf('Create customer %s connection to SunFlow.', $customer->getEmail()));

        Client::create()->connectionContact(
            $this->queryParametersBuilder->getPlatform(),
            $this->loginBuilder->create($customer),
            $this->queryParametersBuilder->create(),
            $this->headerParametersBuilder->create()
        );
    }
}
