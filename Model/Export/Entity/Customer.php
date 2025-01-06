<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Entity;

use FollowTheSun\Connector\Service\Config\Export;
use FollowTheSun\Connector\Service\DateFormat;
use FollowTheSun\Connector\Service\Export\ExportDate;
use Magento\Directory\Model\ResourceModel\Country\CollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class Customer implements EntityInterface
{
    use EntityTrait;

    private const ENTITY_TYPE = 'Customer';

    private array $attributeOptions = [];
    private array $countryCodes = [];
    private ?array $visitorData = null;

    public function __construct(
        private Export $exportConfig,
        private DateFormat $dateFormatService,
        private ExportDate $exportDate,
        private ResourceConnection $resourceConnection,
        private Config $eavConfig,
        private CollectionFactory $countryCollectionFactory
    ) {
    }

    public function buildLinesFromData(array $batch): array
    {
        $lines = [];
        foreach ($batch as $customerData) {
            $lines[] = $this->buildLine($customerData);
        }

        return $lines;
    }

    public function buildLine(array $customerData): array
    {
        $customerUpdatedAt = $this->getFormattedDate($customerData['ce.updated_at'] ?? '');
        $customerAddressUpdatedAt = $this->getFormattedDate($customerData['cae.updated_at'] ?? '');
        $addressCountryCode = (string) $this->getCountryCodeIso3((string) $customerData['cae.country_id']);
        $newsletterSubscribedUpdatedAt = $this->getFormattedDate($customerData['ns.change_status_at'] ?? '');
        $visitorData = $this->getVisitorData()[$customerData['ce.entity_id']] ?? null;
        $lastLoggedInDate = $visitorData ? $this->getFormattedDate($visitorData) : $customerUpdatedAt;

        return [
            'ecommerce_contact_id'      => $customerData['ce.entity_id'],
            'civility'                  => $this->getCivilityLabel((int) $customerData['ce.gender']),
            'lastname'                  => $customerData['ce.lastname'],
            'firstname'                 => $customerData['ce.firstname'],
            'birthdate'                 => $this->getDobDate($customerData['ce.dob']),
            'address_line_1'            => $this->getStreetAddress($customerData['cae.street'], 0),
            'address_line_2'            => $this->getStreetAddress($customerData['cae.street'], 1),
            'address_line_3'            => $this->getStreetAddress($customerData['cae.street'], 2),
            'address_line_4'            => $this->getStreetAddress($customerData['cae.street'], 3),
            'address_postal_code'       => $customerData['cae.postcode'],
            'address_country_code'      => $addressCountryCode,
            'address_city'              => $customerData['cae.city'],
            'phone'                     => $customerData['cae.telephone'],
            'phone_country_code'        => $addressCountryCode,
            'email'                     => $customerData['ce.email'],
            'email_optin'               => $customerData['ns.subscriber_status'] === '1' ?: 0,
            'email_optin_date'          => $newsletterSubscribedUpdatedAt ?: $customerUpdatedAt,
            'language_id'               => '',
            'created_date'              => $this->getFormattedDate($customerData['ce.created_at']),
            'updated_date'              => max($customerUpdatedAt, $customerAddressUpdatedAt),
            'last_logged_in_date'       => $lastLoggedInDate,
            'newsletter_date_subscribe' => $newsletterSubscribedUpdatedAt,
            'store_code'                => $customerData['store.code'],
        ];
    }

    public function getStreetAddress(?string $street = null, int $number = 0): string
    {
        return explode("\n", (string) $street)[$number] ?? '';
    }

    public function getFormattedDate(string $date): string
    {
        return $this->dateFormatService->getDateTimeFormatted(
            $this->dateFormatService->createDateTime($date, true)
        );
    }

    public function getDobDate(?string $customerDob = null): string
    {
        if (!$customerDob) {
            return '';
        }

        $dateTime = $this->dateFormatService->createDateTime($customerDob, false, 'Y-m-d');
        if (!$dateTime || (int) $dateTime->format('Y') < 1900) {
            return '';
        }

        return $this->dateFormatService->getDateFormatted($dateTime);
    }

    public function getDataToExport(int $curPage = 0, int $batchSize = 1000): array
    {
        return $this->resourceConnection->getConnection()->fetchAll($this->getCustomerSelect($curPage, $batchSize));
    }

    public function getCustomerSelect(int $curPage = 0, int $batchSize = 1000): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $customerEntityTable = $connection->getTableName('customer_entity');
        $customerAddressEntityTable = $connection->getTableName('customer_address_entity');
        $newsletterSubscriberTable = $connection->getTableName('newsletter_subscriber');
        $storeTable = $connection->getTableName('store');

        $select = $connection->select()
            ->from(
                ['ce' => $customerEntityTable],
                [
                    'ce.entity_id'  => 'entity_id',
                    'ce.gender'     => 'gender',
                    'ce.firstname'  => 'firstname',
                    'ce.lastname'   => 'lastname',
                    'ce.email'      => 'email',
                    'ce.created_at' => 'created_at',
                    'ce.updated_at' => 'updated_at',
                    'ce.dob'        => 'dob',
                ]
            )->joinLeft(
                ['cae' => $customerAddressEntityTable],
                'ce.default_billing = cae.entity_id',
                [
                    'cae.street'     => 'street',
                    'cae.postcode'   => 'postcode',
                    'cae.country_id' => 'country_id',
                    'cae.city'       => 'city',
                    'cae.telephone'  => 'telephone',
                    'cae.updated_at' => 'updated_at'
                ]
            )->joinLeft(
                ['store' => $storeTable],
                'ce.store_id = store.store_id',
                [
                    'store.code' => 'code',
                ]
            )->joinLeft(
                ['ns' => $newsletterSubscriberTable],
                'ns.customer_id = ce.entity_id',
                [
                    'ns.subscriber_status' => 'subscriber_status',
                    'ns.change_status_at'  => 'change_status_at',
                ]
            )
            ->order('ce.entity_id ASC')
            ->limit($batchSize, $batchSize * $curPage);

        if ($lastExportDate = $this->getLastExportDateForEntityType()) {
            $select->where('ce.updated_at >= ?', $lastExportDate)
                ->orWhere('cae.updated_at >= ?', $lastExportDate);
        }

        return $select;
    }

    public function getCivilityLabel(int $customerGenderValue): string
    {
        if (!isset($this->attributeOptions['gender'])) {
            $genderAttribute = $this->eavConfig->getAttribute('customer', 'gender');
            foreach ($genderAttribute->getSource()->getAllOptions() as $option) {
                $this->attributeOptions['gender'][$option['value']] = $option['label'];
            }
        }

        return $this->attributeOptions['gender'][$customerGenderValue] ?? '';
    }

    public function getVisitorData(): array
    {
        if ($this->visitorData === null) {
            $connection = $this->resourceConnection->getConnection();
            $visitorSelect = $connection->select()
                ->from($connection->getTableName('customer_visitor'), ['customer_id', 'last_visit_at'])
                ->where('customer_id IS NOT NULL')
                ->order('last_visit_at DESC');

            if ($lastExportDate = $this->getLastExportDateForEntityType()) {
                $visitorSelect->where('last_visit_at >= ?', $lastExportDate);
            }

            foreach ($connection->fetchAll($visitorSelect) as $visitorData) {
                if (!isset($this->visitorData[$visitorData['customer_id']])) {
                    $this->visitorData[$visitorData['customer_id']] = $visitorData['last_visit_at'];
                }
            }
        }

        return $this->visitorData ?? [];
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

    public function getLastExportDateForEntityType(): ?string
    {
        if ($this->deltaOnly()) {
            return $this->exportDate->getLastExportDateForEntityType($this->getEntityType());
        }

        return null;
    }

    public function deltaOnly(): bool
    {
        return $this->exportConfig->isDeltaModeEnabled();
    }
}
