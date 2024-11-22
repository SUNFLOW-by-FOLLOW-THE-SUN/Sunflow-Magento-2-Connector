<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Logger;

use Magento\Framework\Logger\Handler\Base;

class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/follow-the-sun-connector.log';
}
