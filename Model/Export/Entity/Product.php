<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Entity;

use FollowTheSun\Connector\Service\Config\Export;
use Magento\Catalog\Setup\CategorySetup;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Store\Model\StoreManagerInterface;

class Product implements EntityInterface
{
    use EntityTrait;

    private const ENTITY_TYPE = 'Product';

    protected array $productAttributes = [];
    protected ?array $productCategories = null;
    protected ?string $productUrlSuffix = null;
    protected ?array $eavAttributes = null;

    public function __construct(
        protected Export $exportConfig,
        protected StoreManagerInterface $storeManager,
        protected ResourceConnection $resourceConnection,
        protected ScopeConfigInterface $scopeConfig,
    ) {
    }

    public function getDataToExport(int $curPage = 0, int $batchSize = 1000): array
    {
        $batchProducts = $this->resourceConnection->getConnection()->fetchAll(
            $this->getProductSelect($curPage, $batchSize)
        );

        $products = [];
        foreach ($batchProducts as $product) {
            $products[$product['cpe.entity_id']] = $product;
        }

        return $products;
    }

    public function buildLinesFromData(array $batch): array
    {
        $lines = [];

        $this->buildAttributes(array_keys($batch));
        foreach ($batch as $productData) {
            $lines[] = $this->buildLine($productData);
        }
        $this->resetAttributes();

        return $lines;
    }

    public function buildLine(array $productData, int $storeId = 0): array
    {
        $id = $productData['cpe.entity_id'];

        return [
            'purchase_good_identifier' => $id,
            'SKU'                      => $productData['cpe.sku'],
            'EAN13'                    => '',
            'reference'                => '',
            'product_name'             => $this->productAttributes['name'][$storeId][$id] ?? '',
            'product_description'      => str_replace(
                ["\r\n", "\n", "\r"],
                '<br>',
                $this->productAttributes['description'][$storeId][$id]
            ),
            'product_category_id'      => $this->productCategories[$id] ?? '',
            'product_url'              => $this->getProductUrl(
                $this->productAttributes['url_key'][$storeId][$id] ?? '',
                $storeId
            ),
            'product_image_url'        => $this->getProductImageUrl(
                $this->productAttributes['image'][$storeId][$id] ?? ''
            ),
        ];
    }

    public function getProductUrl(string $productUrl, int $storeId = 0): string
    {
        return $productUrl ? $this->getStoreUrl($productUrl . $this->getProductUrlSuffix(), $storeId) : '';
    }

    public function getProductImageUrl(string $productImage): string
    {
        return $productImage ? sprintf('%s/media/catalog/product%s', $this->getStoreUrl(), $productImage) : '';
    }

    public function getStoreUrl(string $route = '', ?int $storeId = null): string
    {
        return trim($this->storeManager->getStore($storeId === 0 ? null : $storeId)->getUrl($route), '/');
    }

    public function getProductSelect(int $curPage = 0, int $batchSize = 1000): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $productTable = $connection->getTableName('catalog_product_entity');

        return $connection->select()
            ->from(
                ['cpe' => $productTable],
                [
                    'cpe.entity_id' => 'entity_id',
                    'cpe.sku'       => 'sku'
                ]
            )
            ->order('cpe.entity_id ASC')
            ->limit($batchSize, $batchSize * $curPage);
    }

    public function getAttributeValues(string $attributeCode, ?array $productIds = [], int $storeId = 0): array
    {
        if (!isset($this->productAttributes[$attributeCode][$storeId])) {
            $this->productAttributes[$attributeCode][$storeId] =
                $this->buildAttributeValues($attributeCode, $productIds, $storeId);
        }

        return $this->productAttributes[$attributeCode][$storeId];
    }

    public function buildAttributeValues(
        string $attributeCode,
        ?array $productIds = [],
        int $storeId = 0
    ): array {
        $connection = $this->resourceConnection->getConnection();
        $attribute = $this->getEavAttributes()[$attributeCode];

        $values = $connection->fetchAll(
            $connection->select()
                ->from($connection->getTableName(
                    sprintf('catalog_product_entity_%s', $attribute['backend_type'])
                ), ['entity_id', 'value'])
                ->where('attribute_id = ?', $attribute['attribute_id'])
                ->where('entity_id IN (?)', $productIds)
                ->where('store_id = ?', $storeId)
        );

        $attributeValues = [];
        foreach ($values as $value) {
            $attributeValues[$value['entity_id']] = $value['value'];
        }

        return $attributeValues;
    }

    public function getEavAttributes(): array
    {
        if ($this->eavAttributes === null) {
            $connection = $this->resourceConnection->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(
                        $connection->getTableName('eav_attribute'),
                        ['attribute_code', 'attribute_id', 'backend_type']
                    )->where('entity_type_id = ?', CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID)
            );

            foreach ($rows as $row) {
                $this->eavAttributes[$row['attribute_code']] = $row;
            }
        }

        return $this->eavAttributes;
    }

    public function getProductUrlSuffix(): string
    {
        if ($this->productUrlSuffix === null) {
            $this->productUrlSuffix = (string) $this->scopeConfig->getValue(
                ProductUrlPathGenerator::XML_PATH_PRODUCT_URL_SUFFIX
            );
        }

        return $this->productUrlSuffix;
    }

    public function getProductCategories(?array $productIds = []): array
    {
        if ($this->productCategories) {
            return $this->productCategories;
        }

        $connection = $this->resourceConnection->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    $connection->getTableName('catalog_category_product'),
                    ['category_id', 'product_id']
                )->where('product_id IN (?)', $productIds)
                ->order('category_id DESC')
        );

        $this->productCategories = [];
        foreach ($rows as $row) {
            if (!isset($this->productCategories[$row['product_id']])) {
                $this->productCategories[$row['product_id']] = $row['category_id'];
            }
        }

        return $this->productCategories;
    }

    public function buildAttributes(array $productIds, int $storeId = 0): void
    {
        $this->getAttributeValues('url_key', $productIds, $storeId);
        $this->getAttributeValues('name', $productIds, $storeId);
        $this->getAttributeValues('description', $productIds, $storeId);
        $this->getAttributeValues('image', $productIds, $storeId);
        $this->getProductCategories($productIds);
    }

    public function resetAttributes(): void
    {
        $this->productAttributes = [];
        $this->productCategories = null;
    }
}
