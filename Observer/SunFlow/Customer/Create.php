<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Customer;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Customer\Create as CustomerCreateSunFlowService;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Create implements ObserverInterface
{
    public function __construct(
        private CustomerCreateSunFlowService $customerCreateSunFlowService,
        private Debug $debug
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var CustomerInterface $customer */
        if (!$customer = $observer->getEvent()->getCustomer()) {
            $this->debug->debug('[Customer Create Observer] No customer found.');

            return;
        }

        $this->customerCreateSunFlowService->send($customer);
    }
}
