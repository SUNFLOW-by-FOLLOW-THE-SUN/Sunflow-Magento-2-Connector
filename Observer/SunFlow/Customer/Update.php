<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Customer;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Customer\Update as CustomerUpdateSunFlowService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

class Update implements ObserverInterface
{
    public function __construct(
        private CustomerUpdateSunFlowService $customerUpdateSunFlowService,
        private CustomerRepositoryInterface $customerRepository,
        private Debug $debug
    ) {
    }

    public function execute(Observer $observer): void
    {
        if (!$email = $observer->getEvent()->getEmail()) {
            $this->debug->debug('[Customer Update Observer] Customer email does not exist.');

            return;
        }

        try {
            $customer = $this->customerRepository->get($email);
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->debug->debug(
                sprintf('[Customer/Update] Failed to get customer with email %s : %s', $email, $e->getMessage())
            );

            return;
        }

        $this->customerUpdateSunFlowService->send($customer);
    }
}
