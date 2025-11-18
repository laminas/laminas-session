<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Service\EnvironmentFactoryInterface;
use Laminas\Session\Validator\EnvironmentInterface;

final class TestCustomEnvironmentFactory implements EnvironmentFactoryInterface
{
    public function getEnvironment(): EnvironmentInterface
    {
        return TestCustomEnvironment::fromGlobals($_SERVER);
    }
}
