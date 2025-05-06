<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\ConfigProvider;
use Laminas\Session\ManagerInterface;
use Laminas\Session\Service\SessionConfigFactory;
use Laminas\Session\Service\SessionManagerFactory;
use Laminas\Session\Service\StorageFactory;
use Laminas\Session\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

class ConfigProviderTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $config;

    protected function setUp(): void
    {
        $this->config = (new ConfigProvider())();
    }

    public function testProvidesDependencyConfig(): void
    {
        self::assertArrayHasKey('dependencies', $this->config);
    }

    public function testProvidesCorrectDependencyConfig(): void
    {
        $config = $this->config['dependencies'];

        $factories = $config['factories'];

        self::assertSame($factories[ConfigInterface::class], SessionConfigFactory::class);
        self::assertSame($factories[ManagerInterface::class], SessionManagerFactory::class);
        self::assertSame($factories[StorageInterface::class], StorageFactory::class);
    }
}
