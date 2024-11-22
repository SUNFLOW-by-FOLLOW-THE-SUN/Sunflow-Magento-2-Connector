<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Customer\Admin;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Customer\Update as CustomerUpdateSunFlowService;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class Save implements ObserverInterface
{
    public function __construct(
        private CustomerUpdateSunFlowService $customerUpdateSunFlowService,
        private Debug $debug
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var ?CustomerInterface $customer */
        if (!$customer = $observer->getCustomer()) {
            $this->debug->debug('[Admin Customer Save Observer] No customer found.');

            return;
        }

        $this->customerUpdateSunFlowService->send($customer);
    }
}
