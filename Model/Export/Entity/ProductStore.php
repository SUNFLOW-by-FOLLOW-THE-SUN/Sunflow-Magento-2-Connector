<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Entity;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\Data\StoreInterface;

class ProductStore extends Product
{
    use EntityTrait;

    private const ENTITY_TYPE = 'ProductStore';

    protected ?array $productsWebsites = null;
    protected ?array $stores = null;

    /**
     * @throws NoSuchEntityException
     */
    public function buildLinesFromData(array $batch): array
    {
        $lines = [];

        $productIds = array_keys($batch);
        foreach ($this->getStores() as $store) {
            $storeId = (int) $store->getId();
            $productIdsFiltered = $this->filterProductsIds($productIds, (int) $store->getWebsiteId());

            $this->buildAttributes($productIdsFiltered, $storeId);
            foreach ($productIdsFiltered as $productIdFiltered) {
                $lines[] = $this->buildLine($batch[$productIdFiltered], $storeId, $store->getCode());
            }
            $this->resetAttributes();
        }

        return $lines;
    }

    public function filterProductsIds(array $productIds, int $websiteId): array
    {
        $productsWebsites = $this->getProductIdsForWebsites()[$websiteId] ?? [];

        return array_intersect($productIds, $productsWebsites);
    }

    public function getProductIdsForWebsites(): array
    {
        if ($this->productsWebsites === null) {
            $connection = $this->resourceConnection->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(
                        $connection->getTableName('catalog_product_website'),
                        ['website_id', 'product_id']
                    )
            );

            foreach ($rows as $row) {
                $this->productsWebsites[$row['website_id']][] = $row['product_id'];
            }
        }

        return $this->productsWebsites;
    }

    /**
     * @return StoreInterface[]
     */
    public function getStores(): array
    {
        if ($this->stores === null) {
            $this->stores = [];
            foreach ($this->storeManager->getStores() as $store) {
                if ($store->getId() > 0) {
                    $this->stores[] = $store;
                }
            }
        }

        return $this->stores;
    }

    public function buildLine(array $productData, int $storeId = 0, string $storeCode = 'default'): array
    {
        $line = parent::buildLine($productData, $storeId);
        $line['code_magasin'] = $storeCode;

        return $line;
    }

    public function buildAttributeValues(
        string $attributeCode,
        ?array $productIds = [],
        int $storeId = 0
    ): array {
        $connection = $this->resourceConnection->getConnection();
        $attribute = $this->getEavAttributes()[$attributeCode];

        $subSelect = $connection->select()
            ->from(['sub' => sprintf('catalog_product_entity_%s', $attribute['backend_type'])])
            ->where('sub.attribute_id = ?', $attribute['attribute_id'])
            ->where('sub.entity_id = t.entity_id')
            ->where('sub.store_id = ?', $storeId);

        $values = $connection->fetchAll(
            $connection->select()
                ->from(
                    ['t' => $connection->getTableName(
                        sprintf('catalog_product_entity_%s', $attribute['backend_type'])
                    )],
                    ['entity_id', 'value']
                )
                ->where('attribute_id = ?', $attribute['attribute_id'])
                ->where('entity_id IN (?)', $productIds)
                ->where('store_id = ' . $storeId . ' OR (store_id = 0 AND NOT EXISTS (?))', $subSelect)
        );

        $attributeValues = [];
        foreach ($values as $value) {
            $attributeValues[$value['entity_id']] = $value['value'];
        }

        return $attributeValues;
    }
}
