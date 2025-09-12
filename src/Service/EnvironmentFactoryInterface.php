<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

use Laminas\Session\Validator\EnvironmentInterface;

interface EnvironmentFactoryInterface
{
    public function getEnvironment(): EnvironmentInterface;
}
