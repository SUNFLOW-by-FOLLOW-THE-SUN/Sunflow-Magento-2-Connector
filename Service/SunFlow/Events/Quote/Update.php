<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Events\Quote;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Builder\DTO\Quote as QuoteBuilder;
use FollowTheSun\Connector\Service\SunFlow\Builder\HeaderParameters;
use FollowTheSun\Connector\Service\SunFlow\Builder\QueryParameters;
use FollowTheSun\SunflowSDK\Command\Client;
use Magento\Quote\Api\Data\CartInterface;

class Update
{
    public function __construct(
        private QuoteBuilder $quoteBuilder,
        private HeaderParameters $headerParametersBuilder,
        private QueryParameters $queryParametersBuilder,
        private Debug $debug
    ) {
    }

    public function send(CartInterface $cart): void
    {
        $this->debug->debug(sprintf('Update quote %s to SunFlow.', $cart->getId()));

        Client::create()->updateCommand(
            $this->queryParametersBuilder->getPlatform(),
            $this->quoteBuilder->create($cart),
            $this->queryParametersBuilder->create(),
            $this->headerParametersBuilder->create()
        );
    }
}
