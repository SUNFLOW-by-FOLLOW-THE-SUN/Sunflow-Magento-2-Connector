<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Customer as CustomerDataProvider;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\CustomerAddress as CustomerAddressDataProvider;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Newsletter as NewsletterDataProvider;
use FollowTheSun\SunflowSDK\Contact\Model\SystemCRM360DataContactAddress;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address\AddressModelInterface;

class ContactAddress
{
    public function __construct(
        private CustomerDataProvider $customerDataProvider,
        private CustomerAddressDataProvider $customerAddressDataProvider,
        private NewsletterDataProvider $newsletterDataProvider,
        private SunFlow $sunflowConfig
    ) {
    }

    public function create(CustomerInterface $customer, ?AddressModelInterface $address = null): array
    {
        $isSubscribed = $this->newsletterDataProvider->isSubscribed($customer);
        $newsletterUpdatedAt = $this->newsletterDataProvider->getSubscribeUpdated($customer) ??
            $this->customerDataProvider->getUpdatedAt($customer);
        $countryCode = $this->customerAddressDataProvider->getAddressCountryCode($customer, $address);

        return [
            (new SystemCRM360DataContactAddress())
                ->setAddress1($this->customerAddressDataProvider->getStreetAddress($customer, 0, $address))
                ->setAddress2($this->customerAddressDataProvider->getStreetAddress($customer, 1, $address))
                ->setAddress3($this->customerAddressDataProvider->getStreetAddress($customer, 2, $address))
                ->setAddress4($this->customerAddressDataProvider->getStreetAddress($customer, 3, $address))
                ->setPostCode($this->customerAddressDataProvider->getAddressPostalCode($customer))
                ->setCity($this->customerAddressDataProvider->getAddressCity($customer, $address))
                ->setCountryId($countryCode)
                ->setPostalOptin($isSubscribed)
                ->setPostalOptinDateUTC($newsletterUpdatedAt)
                ->setEmail($this->customerDataProvider->getEmail($customer))
                ->setEmailOptin($isSubscribed)
                ->setEmailOptinDateUTC($newsletterUpdatedAt)
                ->setEmailAdvertiserCreationDateUTC($newsletterUpdatedAt)
                ->setMobilePhone($this->customerAddressDataProvider->getAddressPhone($customer, $address))
                ->setMobilePhoneCountryId($countryCode)
                ->setMobilePhoneOptin($isSubscribed)
                ->setMobilePhoneOptinDateUTC($newsletterUpdatedAt)
                ->setMobilePhoneAdvertiserCreationDateUTC($newsletterUpdatedAt)
                ->setAddressTypeId($this->sunflowConfig->getAddressTypeId())
                ->setBrandId($this->sunflowConfig->getBrandId())
        ];
    }
}
