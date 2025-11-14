<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\EnvironmentInterface;

final class GlobalEnvironmentFactory implements EnvironmentFactoryInterface
{
    /** Retrieves instance of {@see Environment} generated from the $_SERVER superglobal */
    public function getEnvironment(): EnvironmentInterface
    {
        return Environment::fromGlobals($_SERVER);
    }
}
