<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider;

use DateTime;
use FollowTheSun\Connector\Service\DateFormat;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class Customer
{
    private array $attributes = [];

    public function __construct(
        private Config $eavConfig,
        private GroupRepositoryInterface $customerGroupRepository,
        private DateFormat $dateFormat,
        private StoreManagerInterface $storeManager
    ) {
    }

    public function getCivility(CustomerInterface $customer): ?string
    {
        $genderOptions = $this->getAttribute('gender')->getSource()->getAllOptions();
        foreach ($genderOptions as $genderOption) {
            if ($genderOption['value'] === $customer->getGender()) {
                return $genderOption['label'];
            }
        }

        return null;
    }

    public function getLastname(CustomerInterface $customer): string
    {
        return $customer->getLastname();
    }

    public function getFirstname(CustomerInterface $customer): string
    {
        return $customer->getFirstname();
    }

    public function getGroup(CustomerInterface $customer): ?string
    {
        if (!$customerGroupId = $customer->getGroupId()) {
            return null;
        }

        try {
            return $this->customerGroupRepository->getById($customerGroupId)->getCode();
        } catch (NoSuchEntityException | LocalizedException $e) {
            return null;
        }
    }

    public function getBirthDate(CustomerInterface $customer, bool $withTimezone = false): ?DateTime
    {
        $dateTime = $this->dateFormat->createDateTime((string) $customer->getDob(), $withTimezone, 'Y-m-d');
        if (!$dateTime || (int) $dateTime->format('Y') < 1900) {
            return null;
        }

        return $dateTime;
    }

    public function getEmail(CustomerInterface $customer): string
    {
        return $customer->getEmail();
    }

    public function getUpdatedAt(CustomerInterface $customer, bool $withTimezone = false): ?DateTime
    {
        return $this->dateFormat->createDateTime((string) $customer->getUpdatedAt(), $withTimezone);
    }

    public function getCreatedAt(CustomerInterface $customer, bool $withTimezone = false): ?DateTime
    {
        return $this->dateFormat->createDateTime((string) $customer->getCreatedAt(), $withTimezone);
    }

    private function getAttribute(string $attributeCode): AbstractAttribute
    {
        if (!isset($this->attributes[$attributeCode])) {
            $this->attributes[$attributeCode] = $this->eavConfig->getAttribute('customer', $attributeCode);
        }

        return $this->attributes[$attributeCode];
    }

    public function getStoreCode(CustomerInterface $customer): string
    {
        $customerStoreId = $customer->getStoreId();

        try {
            return $this->storeManager->getStore($customerStoreId)->getCode();
        } catch (NoSuchEntityException $e) {
            return (string) $customerStoreId;
        }
    }
}
