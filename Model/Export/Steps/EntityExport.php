<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Steps;

use Exception;
use FollowTheSun\Connector\Exception\ProcessorException;
use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;

class EntityExport implements StepInterface
{
    public function __construct(
        private array $processors
    ) {
    }

    /**
     * @throws ProcessorException
     */
    public function execute(EntityInterface $entity): void
    {
        foreach ($this->processors as $processor) {
            try {
                $processor->process($entity);
            } catch (Exception $e) {
                throw new ProcessorException(sprintf(
                    'Processor %s failed : %s',
                    get_class($processor),
                    $e->getMessage()
                ));
            }
        }
    }
}
