<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Events\Customer;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Builder\DTO\Contact as ContactBuilder;
use FollowTheSun\Connector\Service\SunFlow\Builder\HeaderParameters;
use FollowTheSun\Connector\Service\SunFlow\Builder\QueryParameters;
use FollowTheSun\SunflowSDK\Contact\Client;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;

class Update
{
    public function __construct(
        private ContactBuilder $contactBuilder,
        private HeaderParameters $headerParametersBuilder,
        private QueryParameters $queryParametersBuilder,
        private Debug $debug
    ) {
    }

    public function send(CustomerInterface $customer, ?Address $address = null): void
    {
        $this->debug->debug(sprintf('Update customer %s to SunFlow.', $customer->getEmail()));

        Client::create()->updateContact(
            $this->queryParametersBuilder->getPlatform(),
            $this->contactBuilder->create($customer, $address),
            $this->queryParametersBuilder->create(),
            $this->headerParametersBuilder->create()
        );
    }
}
