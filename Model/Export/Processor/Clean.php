<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Processor;

use Exception;
use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;
use FollowTheSun\Connector\Service\Config\Export;
use FollowTheSun\Connector\Service\Debug;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileSystem;

class Clean implements ProcessorInterface
{
    public function __construct(
        private Export $exportConfig,
        private FileSystem $fileSystem,
        private DirectoryList $directoryList,
        private Debug $debug
    ) {
    }

    /**
     * @throws FileSystemException
     * @throws Exception
     */
    public function process(EntityInterface $entity): void
    {
        if (!$this->exportConfig->isCleanFilesEnabled()) {
            $this->debug->debug('[Export Clean Processor] Clean files is disabled.');

            return;
        }

        $this->deleteFile($entity->getFilename());
    }

    /**
     * @throws FileSystemException
     */
    public function deleteFile(string $filename): void
    {
        $path = sprintf(
            '%s/%s/%s',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            File::EXPORT_PATH,
            $filename
        );

        if ($this->fileSystem->isExists($path)) {
            $this->fileSystem->deleteFile($path);
        }
    }
}
