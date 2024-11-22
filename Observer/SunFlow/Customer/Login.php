<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Customer;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Customer\Login as CustomerLoginSunFlowService;
use Magento\Customer\Model\Customer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Login implements ObserverInterface
{
    public function __construct(
        private CustomerLoginSunFlowService $customerLoginSunFlowService,
        private Debug $debug
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Customer $customer */
        if (!$customer = $observer->getEvent()->getCustomer()) {
            $this->debug->debug('[Customer Login Observer] No customer found.');

            return;
        }

        $this->customerLoginSunFlowService->send($customer->getDataModel());
    }
}
