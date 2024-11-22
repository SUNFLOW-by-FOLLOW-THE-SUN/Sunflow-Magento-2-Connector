<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Steps;

use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;

interface StepInterface
{
    public function execute(EntityInterface $entity): void;
}
