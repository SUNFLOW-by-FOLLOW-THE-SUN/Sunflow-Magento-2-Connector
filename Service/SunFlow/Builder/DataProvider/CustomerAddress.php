<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;

class CustomerAddress
{
    private array $addresses = [];
    private array $countryCodes = [];

    public function __construct(
        private AddressRepositoryInterface $addressRepository,
        private CollectionFactory $countryCollectionFactory
    ) {
    }

    public function getStreetAddress(CustomerInterface $customer, int $lineNumber, ?Address $address = null): ?string
    {
        return $this->getAddress($customer, $address)?->getStreet()[$lineNumber] ?? null;
    }

    public function getAddressPostalCode(CustomerInterface $customer, ?Address $address = null): ?string
    {
        return $this->getAddress($customer, $address)?->getPostcode();
    }

    public function getAddressCountryCode(CustomerInterface $customer, ?Address $address = null): ?string
    {
        if (!$address = $this->getAddress($customer, $address)) {
            return null;
        }

        return $this->getCountryCodeIso3($address->getCountryId());
    }

    public function getAddressCity(CustomerInterface $customer, ?Address $address = null): ?string
    {
        return $this->getAddress($customer, $address)?->getCity();
    }

    public function getAddressPhone(CustomerInterface $customer, ?Address $address = null): ?string
    {
        return $this->getAddress($customer, $address)?->getTelephone();
    }

    public function getAddress(CustomerInterface $customer, ?Address $address = null): ?AddressInterface
    {
        $customerId = $customer->getId();
        if (isset($this->addresses[$customerId])) {
            return $this->addresses[$customerId];
        }

        if ($address) {
            return $this->addresses[$customerId] = $address->getDataModel();
        }

        $customerAddressId = $this->getCustomerAddressId($customer);
        if (!$customerAddressId) {
            $this->addresses[$customerId] = null;

            return null;
        }

        try {
            $this->addresses[$customerId] = $this->addressRepository->getById($customerAddressId);
        } catch (LocalizedException $e) {
            $this->addresses[$customerId] = null;
        }

        return $this->addresses[$customerId];
    }

    public function getCustomerAddressId(CustomerInterface $customer): ?int
    {
        return (int) $customer->getDefaultBilling() ? (int) $customer->getDefaultShipping() : null;
    }

    public function getCountryCodeIso3(string $countryId): ?string
    {
        if (empty($this->countryCodes)) {
            foreach ($this->countryCollectionFactory->create()->getItems() as $country) {
                $this->countryCodes[$country->getCountryId()] = $country->getData('iso3_code');
            }
        }

        return $this->countryCodes[$countryId] ?? null;
    }
}
