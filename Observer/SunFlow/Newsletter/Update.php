<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Newsletter;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Newsletter\Update as NewsletterUpdateSunFlowService;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;

class Update implements ObserverInterface
{
    public function __construct(
        private CustomerRepositoryInterface $customerRepository,
        private NewsletterUpdateSunFlowService $newsletterUpdateSunFlowService,
        private Debug $debug
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var Subscriber $subscriber */
        if (!$subscriber = $observer->getSubscriber()) {
            return;
        }

        try {
            $customer = $this->customerRepository->getById((int) $subscriber->getCustomerId());
        } catch (NoSuchEntityException | LocalizedException $e) {
            $this->debug->debug(sprintf(
                '[Newsletter Create Observer] Cannot to get customer from subscriber data : %s',
                $e->getMessage()
            ));

            return;
        }

        $this->newsletterUpdateSunFlowService->send($customer);
    }
}
