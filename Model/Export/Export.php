<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export;

use DateTime;
use Exception;
use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;
use FollowTheSun\Connector\Model\Export\Steps\StepInterface;
use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\Export\ExportDate;

class Export implements ExportInterface
{
    /**
     * @param StepInterface[] $steps
     */
    public function __construct(
        private Debug $debug,
        private EntityInterface $entity,
        private ExportDate $exportDateService,
        private array $steps,
    ) {
    }

    public function export(): void
    {
        $this->debug->debug(sprintf('%s entity export started.', $this->entity->getEntityType()));
        $start = microtime(true);
        $this->exportEntity();
        $end = microtime(true);

        $this->debug->debug(sprintf(
            '%s entity export ended. Duration : %s seconds',
            $this->entity->getEntityType(),
            $end - $start
        ));
    }

    public function exportEntity(): void
    {
        $dateExport = $this->getExportDate();

        try {
            foreach ($this->steps as $step) {
                $step->execute($this->entity);
            }
            $this->saveExportDate($dateExport);
        } catch (Exception $exception) {
            $this->debug->debug(sprintf(
                '[Export] Exception %s : %s',
                get_class($exception),
                $exception->getMessage()
            ));
        }
    }

    public function saveExportDate(string $datetime): void
    {
        $entityType = $this->entity->getEntityType();

        $this->debug->debug(sprintf('Update export date in database for entity %s : %s', $entityType, $datetime));
        $this->exportDateService->saveExportDateForEntity($entityType, $datetime);
    }

    public function getExportDate(): string
    {
        return (new DateTime())->format('Y-m-d H:i:s');
    }
}
