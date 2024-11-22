<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Quote;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Quote\Update as QuoteUpdateSunFlowService;
use Magento\Checkout\Model\Cart\CartInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Update implements ObserverInterface
{
    public function __construct(
        private QuoteUpdateSunFlowService $quoteUpdateSunFlowService,
        private Debug $debug
    ) {
    }

    public function execute(Observer $observer): void
    {
        /* @var CartInterface $quote */
        if (!$cart = $observer->getEvent()->getCart()) {
            $this->debug->debug('[Quote Update Observer] No quote found.');

            return;
        }

        $this->quoteUpdateSunFlowService->send($cart->getQuote());
    }
}
