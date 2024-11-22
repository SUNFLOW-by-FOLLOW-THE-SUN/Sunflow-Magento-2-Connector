<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Module;

use FollowTheSun\Connector\Service\Debug;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

class GetVersion implements GetVersionInterface
{
    public const MODULE_NAME = 'FollowTheSun_Connector';

    public function __construct(
        private ComponentRegistrarInterface $componentRegistrar,
        private File $fileSystem,
        private Debug $debug
    ) {
    }

    public function resolve(): string
    {
        $moduleDir = $this->componentRegistrar->getPath(
            ComponentRegistrar::MODULE,
            self::MODULE_NAME
        );

        try {
            $composerJson = $this->fileSystem->fileGetContents(sprintf(
                '%s/composer.json',
                $moduleDir
            ));
        } catch (FileSystemException $e) {
            $this->debug->debug(sprintf(
                'Unable to resolve version module : %s',
                $e->getMessage()
            ));

            return '';
        }

        $composerJson = json_decode($composerJson, true);

        if (empty($composerJson['version'])) {
            return 'Version is not available in composer.json';
        }

        return $composerJson['version'];
    }
}
