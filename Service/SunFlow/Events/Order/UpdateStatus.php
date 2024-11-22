<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Events\Order;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Builder\DTO\StatusCommand as StatusOrderBuilder;
use FollowTheSun\Connector\Service\SunFlow\Builder\HeaderParameters;
use FollowTheSun\Connector\Service\SunFlow\Builder\QueryParameters;
use FollowTheSun\SunflowSDK\Command\Client;
use Magento\Sales\Api\Data\OrderInterface;

class UpdateStatus
{
    public function __construct(
        private StatusOrderBuilder $statusOrderBuilder,
        private HeaderParameters $headerParametersBuilder,
        private QueryParameters $queryParametersBuilder,
        private Debug $debug
    ) {
    }

    public function send(OrderInterface $order, ?string $newStatusLabel = null): void
    {
        $this->debug->debug(sprintf(
            'Order %s status changed to %s and send to SunFlow.',
            $order->getIncrementId(),
            $newStatusLabel ?? $order->getStatus()
        ));

        Client::create()->setStatusCommand(
            $this->queryParametersBuilder->getPlatform(),
            $this->statusOrderBuilder->create($order, $newStatusLabel),
            $this->queryParametersBuilder->create(),
            $this->headerParametersBuilder->create()
        );
    }
}
