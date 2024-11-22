<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Events\Order;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Builder\DTO\Order as OrderBuilder;
use FollowTheSun\Connector\Service\SunFlow\Builder\HeaderParameters;
use FollowTheSun\Connector\Service\SunFlow\Builder\QueryParameters;
use FollowTheSun\SunflowSDK\Command\Client;
use Magento\Sales\Api\Data\OrderInterface;

class Update
{
    public function __construct(
        private OrderBuilder $orderBuilder,
        private HeaderParameters $headerParametersBuilder,
        private QueryParameters $queryParametersBuilder,
        private Debug $debug
    ) {
    }

    public function send(OrderInterface $order): void
    {
        $this->debug->debug(sprintf('Update order %s to SunFlow.', $order->getIncrementId()));

        Client::create()->updateCommand(
            $this->queryParametersBuilder->getPlatform(),
            $this->orderBuilder->create($order),
            $this->queryParametersBuilder->create(),
            $this->headerParametersBuilder->create()
        );
    }
}
