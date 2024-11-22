<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Events\Customer;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Builder\DTO\Contact as ContactBuilder;
use FollowTheSun\Connector\Service\SunFlow\Builder\HeaderParameters;
use FollowTheSun\Connector\Service\SunFlow\Builder\QueryParameters;
use FollowTheSun\SunflowSDK\Contact\Client;
use Magento\Customer\Api\Data\CustomerInterface;

class Create
{
    public function __construct(
        private ContactBuilder $contactBuilder,
        private HeaderParameters $headerParametersBuilder,
        private QueryParameters $queryParametersBuilder,
        private Debug $debug
    ) {
    }

    public function send(CustomerInterface $customer): void
    {
        $this->debug->debug(sprintf('Create customer %s to SunFlow.', $customer->getEmail()));

        Client::create()->createContact(
            $this->queryParametersBuilder->getPlatform(),
            $this->contactBuilder->create($customer),
            $this->queryParametersBuilder->create(),
            $this->headerParametersBuilder->create()
        );
    }
}
