<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Processor;

use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\WriteInterface;

class File implements ProcessorInterface
{
    public const DELIMITER = '|';
    public const EXPORT_PATH = 'export/follow-the-sun';
    public const BATCH_SIZE = 10000;

    public function __construct(
        private Filesystem $filesystem
    ) {
    }

    /**
     * @throws FileSystemException
     */
    public function process(EntityInterface $entity): void
    {
        $stream = $this->getFileStream($entity->getFilename());
        $header = false;

        $curPage = 0;
        while ($batch = $entity->getDataToExport($curPage++, self::BATCH_SIZE)) {
            foreach ($entity->buildLinesFromData($batch) as $line) {
                if (!$header) {
                    $stream->writeCsv(array_keys($line), self::DELIMITER);
                    $header = true;
                }

                $stream->writeCsv($line, self::DELIMITER);
            }
        }

        $this->endFileStream($stream);
    }

    /**
     * @throws FileSystemException
     */
    public function getFileStream(string $filename): WriteInterface
    {
        $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $directory->create(self::EXPORT_PATH);

        $stream = $directory->openFile(sprintf('%s/%s', self::EXPORT_PATH, $filename), 'w+');
        $stream->lock();

        return $stream;
    }

    public function endFileStream(WriteInterface $stream): void
    {
        $stream->close();
    }
}
