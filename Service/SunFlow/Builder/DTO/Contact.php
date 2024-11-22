<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Customer as CustomerDataProvider;
use FollowTheSun\SunflowSDK\Constant\AttachedStoreTypeId;
use FollowTheSun\SunflowSDK\Contact\Model\SystemCRM360DataContact;
use FollowTheSun\SunflowSDK\Contact\Model\SystemCRM360DataContactAttachedStores;
use FollowTheSun\SunflowSDK\Contact\Model\SystemCRM360DataContactAttachedStoresStoreExternalKey;
use FollowTheSun\SunflowSDK\Contact\Model\SystemCRM360DataContactIdentifier;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\AddressModelInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Contact
{
    public function __construct(
        private SunFlow $sunflowConfig,
        private CustomerDataProvider $customerDataProvider,
        private StoreManagerInterface $storeManager,
        private ContactAddress $contactAddressBuilder
    ) {
    }

    public function create(CustomerInterface $customer, ?AddressModelInterface $address = null): SystemCRM360DataContact
    {
        return (new SystemCRM360DataContact())
            ->setCivility($this->customerDataProvider->getCivility($customer))
            ->setFirstName($this->customerDataProvider->getFirstName($customer))
            ->setLastName($this->customerDataProvider->getLastName($customer))
            ->setDefaultGroup($this->customerDataProvider->getGroup($customer))
            ->setBirthDate($this->customerDataProvider->getBirthDate($customer))
            ->setCreationDate($this->customerDataProvider->getCreatedAt($customer, true))
            ->setAdvertiserCreationDateUTC($this->customerDataProvider->getUpdatedAt($customer))
            ->setZoneId($this->sunflowConfig->getZoneId())
            ->setAddresses($this->contactAddressBuilder->create($customer, $address))
            ->setIdentifiers($this->buildIdentifiers($customer))
            ->setAttachedStores($this->buildAttachedStores($customer));
    }

    public function buildIdentifiers(CustomerInterface $customer): array
    {
        return [
            (new SystemCRM360DataContactIdentifier())
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

        $contactAttachedStoresDTO = (new SystemCRM360DataContactAttachedStores());
        $contactAttachedStoresDTO->setStoreExternalKey(
            (new SystemCRM360DataContactAttachedStoresStoreExternalKey())
                ->setStoreIdentifier($storeIdentifier)
                ->setSourceId($this->sunflowConfig->getSourceId())
        )->setAttachedStoreTypeId(AttachedStoreTypeId::MANUAL);

        return [$contactAttachedStoresDTO];
    }
}
