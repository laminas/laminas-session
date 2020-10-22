<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use Laminas\Session\Module;
use PHPUnit\Framework\TestCase;

class ModuleTest extends TestCase
{
    public function testConfigProvidesServiceManagerConfiguration(): void
    {
        $config = (new Module())->getConfig();

        self::assertArrayHasKey('service_manager', $config);
    }
}
