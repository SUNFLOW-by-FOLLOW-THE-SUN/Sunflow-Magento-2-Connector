<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Processor;

use Exception;
use FollowTheSun\Connector\Model\Export\Entity\EntityInterface;
use FollowTheSun\Connector\Service\Ftp\FtpInterface as FtpServiceInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileSystem;

class Ftp implements ProcessorInterface
{
    public function __construct(
        private DirectoryList $directoryList,
        private FileSystem $fileSystem,
        private FtpServiceInterface $ftp
    ) {
    }

    /**
     * @throws FileSystemException
     * @throws Exception
     */
    public function process(EntityInterface $entity): void
    {
        $fileName = $entity->getFilename();

        $file = $this->getFileOpen($fileName);
        $this->ftp->export($fileName, $file);
        $this->fileSystem->fileClose($file);
    }

    /**
     * @return resource file
     *
     * @throws FileSystemException
     */
    public function getFileOpen(string $filename)
    {
        $path = sprintf(
            '%s/%s/%s',
            $this->directoryList->getPath(DirectoryList::VAR_DIR),
            File::EXPORT_PATH,
            $filename
        );

        return $this->fileSystem->fileOpen($path, 'r');
    }
}
