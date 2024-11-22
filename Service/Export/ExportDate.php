<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Export;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class ExportDate
{
    private const EXPORT_DATE_TABLE_NAME = 'followthesun_export_date';
    private const LAST_EXPORT_DATE_COLUMN = 'last_export_date';
    private const ENTITY_TYPE_COLUMN = 'entity_type';

    private ?AdapterInterface $connection = null;

    public function __construct(
        private ResourceConnection $resourceConnection
    ) {
    }

    public function saveExportDateForEntity(string $entityType, string $datetime): void
    {
        $connection = $this->getConnection();

        $connection->insertOnDuplicate(
            $connection->getTableName(self::EXPORT_DATE_TABLE_NAME),
            [
                self::LAST_EXPORT_DATE_COLUMN => $datetime,
                self::ENTITY_TYPE_COLUMN      => $entityType,
            ],
            [
                self::LAST_EXPORT_DATE_COLUMN,
            ]
        );
    }

    public function getLastExportDateForEntityType(string $entityType): ?string
    {
        $connection = $this->getConnection();

        return (string) $connection->fetchOne(
            $connection->select()
                ->from($connection->getTableName(self::EXPORT_DATE_TABLE_NAME), self::LAST_EXPORT_DATE_COLUMN)
                ->where(self::ENTITY_TYPE_COLUMN . ' = ?', $entityType)
        );
    }

    private function getConnection(): AdapterInterface
    {
        if ($this->connection === null) {
            $this->connection = $this->resourceConnection->getConnection();
        }

        return $this->connection;
    }
}
