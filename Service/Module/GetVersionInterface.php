<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\Module;

interface GetVersionInterface
{
    public function resolve(): string;
}
