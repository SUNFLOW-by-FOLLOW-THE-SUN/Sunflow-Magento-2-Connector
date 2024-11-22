<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Steps;

use FollowTheSun\Connector\Exception\ExportEntityDisabledException;
use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;

class EntityExportEnabled implements StepInterface
{
    /**
     * @throws ExportEntityDisabledException
     */
    public function execute(EntityInterface $entity): void
    {
        if (!$entity->canExport()) {
            throw new ExportEntityDisabledException(sprintf(
                '[Export] Export %s is disabled',
                $entity->getEntityType()
            ));
        }
    }
}
