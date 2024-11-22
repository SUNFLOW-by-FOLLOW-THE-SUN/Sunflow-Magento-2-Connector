<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Customer as CustomerDataProvider;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Newsletter as NewsletterDataProvider;
use FollowTheSun\SunflowSDK\Constant\AttachedStoreTypeId;
use FollowTheSun\SunflowSDK\Newsletter\Model\SystemCRM360DataNewsletter;
use FollowTheSun\SunflowSDK\Newsletter\Model\SystemCRM360DataNewsletterAddress;
use FollowTheSun\SunflowSDK\Newsletter\Model\SystemCRM360DataNewsletterAttachedStores;
use FollowTheSun\SunflowSDK\Newsletter\Model\SystemCRM360DataNewsletterAttachedStoresStoreExternalKey;
use FollowTheSun\SunflowSDK\Newsletter\Model\SystemCRM360DataNewsletterIdentifier;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Newsletter
{
    public function __construct(
        private SunFlow $sunflowConfig,
        private CustomerDataProvider $customerDataProvider,
        private NewsletterDataProvider $newsletterDataProvider,
        private StoreManagerInterface $storeManager
    ) {
    }

    public function create(CustomerInterface $customer): SystemCRM360DataNewsletter
    {
        $newsletterCreatedAt = $this->newsletterDataProvider->getSubscribeCreated($customer, true) ??
            $this->customerDataProvider->getCreatedAt($customer, true);

        return (new SystemCRM360DataNewsletter())
            ->setZoneId($this->sunflowConfig->getZoneId())
            ->setCreationDate($newsletterCreatedAt)
            ->setAddresses($this->buildAddresses($customer))
            ->setIdentifiers($this->buildIdentifiers($customer))
            ->setAttachedStores($this->buildAttachedStores($customer));
    }

    public function buildAddresses(CustomerInterface $customer): array
    {
        $isSubscribed = $this->newsletterDataProvider->isSubscribed($customer);
        $newsletterUpdatedAt = $this->newsletterDataProvider->getSubscribeUpdated($customer) ??
            $this->customerDataProvider->getUpdatedAt($customer);

        return [
            (new SystemCRM360DataNewsletterAddress())
                ->setEmail($this->customerDataProvider->getEmail($customer))
                ->setEmailOptin($isSubscribed)
                ->setEmailOptinDateUTC($newsletterUpdatedAt)
                ->setEmailAdvertiserCreationDateUTC($newsletterUpdatedAt)
                ->setAddressTypeId($this->sunflowConfig->getAddressTypeId())
                ->setBrandId($this->sunflowConfig->getBrandId())
        ];
    }

    public function buildIdentifiers(CustomerInterface $customer): array
    {
        return [
            (new SystemCRM360DataNewsletterIdentifier())
                ->setIdentifier($customer->getId())
                ->setSourceId($this->sunflowConfig->getSourceId())
        ];
    }

    public function buildAttachedStores(CustomerInterface $customer): array
    {
        $storeId = $customer->getStoreId();
        try {
            $storeIdentifier = $this->storeManager->getStore($customer->getStoreId())->getCode();
        } catch (NoSuchEntityException $e) {
            $storeIdentifier = $storeId;
        }

        $newsletterAttachedStoresDTO = (new SystemCRM360DataNewsletterAttachedStores());
        $newsletterAttachedStoresDTO->setStoreExternalKey(
            (new SystemCRM360DataNewsletterAttachedStoresStoreExternalKey())
                ->setStoreIdentifier($storeIdentifier)
                ->setSourceId($this->sunflowConfig->getSourceId())
        )->setAttachedStoreTypeId(AttachedStoreTypeId::MANUAL);

        return [$newsletterAttachedStoresDTO];
    }
}
