<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Customer;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Customer\Update as CustomerUpdateSunFlowService;
use Magento\Customer\Model\Address;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AddressSave implements ObserverInterface
{
    public function __construct(
        private CustomerUpdateSunFlowService $customerUpdateSunFlowService,
        private Debug $debug
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var ?Address $address */
        if (
            (!$address = $observer->getCustomerAddress())
            || (!$customerDataModel = $address->getCustomer()?->getDataModel())
        ) {
            $this->debug->debug('[AddressSave Observer] No customer data found.');

            return;
        }

        if ($this->isDefaultBilling($address)) {
            $this->customerUpdateSunFlowService->send($customerDataModel, $address);
        }
    }

    private function isDefaultBilling(Address $address): bool
    {
        return $address->getId() && $address->getId() == $address->getCustomer()->getDefaultBilling()
            || $address->getIsPrimaryBilling()
            || $address->getIsDefaultBilling();
    }
}
