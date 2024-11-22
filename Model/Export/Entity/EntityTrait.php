<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Entity;

use DateTimeImmutable;

trait EntityTrait
{
    public function getFileName(): string
    {
        return sprintf('%s_web_%s.txt', $this->getEntityType(), (new DateTimeImmutable())->format('Ymd'));
    }

    public function getEntityType(): string
    {
        return self::ENTITY_TYPE;
    }

    public function canExport(): bool
    {
        $method = sprintf('isExportEntity%sEnabled', $this->getEntityType());

        return $this->exportConfig->$method();
    }
}
