<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Steps;

use FollowTheSun\Connector\Exception\EmptyDataEntityException;
use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;

class EntityHasDataToExport implements StepInterface
{
    /**
     * @throws EmptyDataEntityException
     */
    public function execute(EntityInterface $entity): void
    {
        if (empty($entity->getDataToExport())) {
            throw new EmptyDataEntityException(sprintf(
                '[Export] There is no data to export for entity %s',
                $entity->getEntityType()
            ));
        }
    }
}
