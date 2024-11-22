<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Command;

use FollowTheSun\Connector\Model\Export\ExportInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends SymfonyCommand
{
    public function __construct(
        private ExportInterface $export,
        private State $state,
        ?string $name = null,
        private ?string $description = null,
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription($this->description);
    }

    /**
     * @throws LocalizedException
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->getAreaCode();
        } catch (LocalizedException) {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        }

        $this->export->export();

        return SymfonyCommand::SUCCESS;
    }
}
