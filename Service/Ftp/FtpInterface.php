<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Ftp;

interface FtpInterface
{
    /**
     * @param resource $file
     */
    public function export(string $filename, $file): void;
}
