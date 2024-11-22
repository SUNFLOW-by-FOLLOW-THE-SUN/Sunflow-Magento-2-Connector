<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Processor;

use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;

interface ProcessorInterface
{
    public function process(EntityInterface $entity);
}
