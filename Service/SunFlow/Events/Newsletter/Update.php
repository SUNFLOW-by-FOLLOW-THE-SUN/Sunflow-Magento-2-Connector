<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Events\Newsletter;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Builder\DTO\Newsletter as NewsletterBuilder;
use FollowTheSun\Connector\Service\SunFlow\Builder\HeaderParameters;
use FollowTheSun\Connector\Service\SunFlow\Builder\QueryParameters;
use FollowTheSun\SunflowSDK\Newsletter\Client;
use Magento\Customer\Api\Data\CustomerInterface;

class Update
{
    public function __construct(
        private NewsletterBuilder $newsletterBuilder,
        private HeaderParameters $headerParametersBuilder,
        private QueryParameters $queryParametersBuilder,
        private Debug $debug
    ) {
    }

    public function send(CustomerInterface $customer): void
    {
        $this->debug->debug(sprintf('Update newsletter subscriber %s to SunFlow.', $customer->getEmail()));

        Client::create()->updateNewsletter(
            $this->queryParametersBuilder->getPlatform(),
            $this->newsletterBuilder->create($customer),
            $this->queryParametersBuilder->create(),
            $this->headerParametersBuilder->create()
        );
    }
}
