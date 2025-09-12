<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\EnvironmentInterface;

final class EnvironmentFactory implements EnvironmentFactoryInterface
{
    public function getEnvironment(): EnvironmentInterface
    {
        return Environment::fromGlobals($_SERVER);
    }
}
