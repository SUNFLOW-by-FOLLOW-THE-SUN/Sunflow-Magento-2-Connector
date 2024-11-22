<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider;

use DateTime;
use FollowTheSun\Connector\Service\DateFormat;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Newsletter\Model\ResourceModel\Subscriber\CollectionFactory as SubscriberCollectionFactory;
use Magento\Newsletter\Model\Subscriber;

class Newsletter
{
    private array $subscribers = [];

    public function __construct(
        private SubscriberCollectionFactory $subscriberCollectionFactory,
        private DateFormat $dateFormat
    ) {
    }

    public function isSubscribed(CustomerInterface $customer): bool
    {
        $subscriberData = $this->getSubscriberData($customer);
        if (!$subscriberData || empty($subscriberData->getData())) {
            return false;
        }

        return (int) $subscriberData->getSubscriberStatus() === Subscriber::STATUS_SUBSCRIBED;
    }

    public function getSubscribeUpdated(CustomerInterface $customer, bool $withTimezone = false): ?DateTime
    {
        $subscriberData = $this->getSubscriberData($customer);
        if (!$subscriberData || empty($subscriberData->getData())) {
            return null;
        }

        if (!$updatedAtDate = $this->dateFormat->createDateTime($subscriberData->getChangeStatusAt(), $withTimezone)) {
            return null;
        }

        return $updatedAtDate;
    }

    public function getSubscribeCreated(CustomerInterface $customer, bool $withTimezone = false): ?DateTime
    {
        $subscriberData = $this->getSubscriberData($customer);
        if (!$subscriberData || empty($subscriberData->getData())) {
            return null;
        }

        if (!$createdAtDate = $this->dateFormat->createDateTime($subscriberData->getCreatedAt(), $withTimezone)) {
            return null;
        }

        return $createdAtDate;
    }

    private function getSubscriberData(CustomerInterface $customer): ?Subscriber
    {
        if (empty($this->subscribers)) {
            foreach ($this->subscriberCollectionFactory->create()->getItems() as $subscriber) {
                $this->subscribers[$subscriber->getCustomerId()] = $subscriber;
            }
        }

        return $this->subscribers[$customer->getId()] ?? null;
    }
}
