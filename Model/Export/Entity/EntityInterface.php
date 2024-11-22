<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Entity;

interface EntityInterface
{
    public function getEntityType(): string;

    public function canExport(): bool;

    public function getFilename(): string;

    public function getDataToExport(int $curPage = 0, int $batchSize = 1000): array;

    public function buildLinesFromData(array $batch): array;
}
