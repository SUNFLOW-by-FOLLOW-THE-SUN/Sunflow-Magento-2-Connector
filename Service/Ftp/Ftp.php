<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Ftp;

use Exception;
use FollowTheSun\Connector\Service\Config\Ftp as FtpConfig;
use Magento\Framework\HTTP\Client\Curl as CurlClient;

class Ftp implements FtpInterface
{
    public function __construct(
        private CurlClient $curl,
        private FtpConfig $ftpConfig
    ) {
    }

    /**
     * The FTP given by FollowTheSun needs an FTP SSL connection with implicit FTP over TLS.
     * It can't be done with @ftp_connect or @ftp_ssl_connect because it's only explicit.
     * We have to curl instead.
     *
     * @param resource $file
     *
     * @throws Exception
     */
    public function export(string $filename, $file): void
    {
        $url = $this->buildUrl($filename);

        $this->curl->setOptions([
            CURLOPT_PORT           => $this->ftpConfig->getFtpPort(),
            CURLOPT_USERPWD        => sprintf(
                '%s:%s',
                $this->ftpConfig->getFtpUsername(),
                $this->ftpConfig->getFtpPassword()
            ),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USE_SSL        => CURLFTPSSL_TRY,
            CURLOPT_FTPSSLAUTH     => CURLFTPAUTH_TLS,
            CURLOPT_UPLOAD         => 1,
            CURLOPT_INFILE         => $file,
            CURLOPT_FTP_SSL        => CURLFTPSSL_CONTROL
        ]);

        $this->curl->get($url);
    }

    public function buildUrl(string $filename): string
    {
        return sprintf('ftps://%s/Import/%s', $this->ftpConfig->getFtpHost(), $filename);
    }
}
