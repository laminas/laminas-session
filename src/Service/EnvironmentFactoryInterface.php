<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

use Laminas\Session\Validator\EnvironmentInterface;

/**
 * Allows users to override the default {@see GlobalEnvironmentFactory} in DI
 * in order to return custom {@see Environment} implementations
 */
interface EnvironmentFactoryInterface
{
    /** Retrieve an instance of {@see EnvironmentInterface} based on the configured factory */
    public function getEnvironment(): EnvironmentInterface;
}
