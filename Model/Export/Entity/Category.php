<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Entity;

use FollowTheSun\Connector\Service\Config\Export;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class Category implements EntityInterface
{
    use EntityTrait;

    private const ENTITY_TYPE = 'ProductCategory';

    private ?array $categoryNames = null;

    public function __construct(
        private Export $exportConfig,
        private ResourceConnection $resourceConnection
    ) {
    }

    public function buildLinesFromData(array $batch): array
    {
        $lines = [];

        foreach ($batch as $categoryData) {
            $lines[] = $this->buildLine($categoryData);
        }

        return $lines;
    }

    public function buildLine(array $categoryData): array
    {
        return [
            'category_code'        => $categoryData['cce.entity_id'],
            'category_name'        => $this->getCategoryNames()[$categoryData['cce.entity_id']],
            'parent_category_code' => $categoryData['cce.parent_id']
        ];
    }

    public function getDataToExport(int $curPage = 0, int $batchSize = 1000): array
    {
        return $this->resourceConnection->getConnection()->fetchAll($this->getCategorySelect($curPage, $batchSize));
    }

    public function getCategorySelect(int $curPage = 0, int $batchSize = 1000): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $categoryTable = $connection->getTableName('catalog_category_entity');

        return $connection->select()
            ->from(
                ['cce' => $categoryTable],
                [
                    'cce.entity_id' => 'entity_id',
                    'cce.parent_id' => 'parent_id'
                ]
            )
            ->order('cce.entity_id ASC')
            ->limit($batchSize, $batchSize * $curPage);
    }

    public function getCategoryNames(): array
    {
        if (!$this->categoryNames) {
            $connection = $this->resourceConnection->getConnection();
            $attribute = $connection->fetchRow(
                $connection->select()
                    ->from(
                        $connection->getTableName('eav_attribute'),
                        ['attribute_id', 'backend_type']
                    )->where('entity_type_id = ?', CategorySetup::CATEGORY_ENTITY_TYPE_ID)
                    ->where('attribute_code = ?', 'name')
            );

            $values = $connection->fetchAll(
                $connection->select()
                    ->from($connection->getTableName(
                        sprintf('catalog_category_entity_%s', $attribute['backend_type'])
                    ), ['entity_id', 'value'])
                    ->where('attribute_id = ?', $attribute['attribute_id'])
            );

            foreach ($values as $value) {
                $this->categoryNames[$value['entity_id']] = $value['value'];
            }
        }

        return $this->categoryNames;
    }
}
