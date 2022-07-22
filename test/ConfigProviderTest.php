<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\ConfigProvider;
use Laminas\Session\ManagerInterface;
use Laminas\Session\Service\ContainerAbstractServiceFactory;
use Laminas\Session\Service\SessionConfigFactory;
use Laminas\Session\Service\SessionManagerFactory;
use Laminas\Session\Service\StorageFactory;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Zend\Session\Config\ConfigInterface as LegacyConfigInterface;
use Zend\Session\ManagerInterface as LegacyManagerInterface;
use Zend\Session\SessionManager as LegacySessionManager;
use Zend\Session\Storage\StorageInterface as LegacyStorageInterface;

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

        $abstractFactories = $config['abstract_factories'];
        $aliases           = $config['aliases'];
        $factories         = $config['factories'];

        self::assertContains(ContainerAbstractServiceFactory::class, $abstractFactories);
        self::assertSame($aliases[SessionManager::class], ManagerInterface::class);
        self::assertSame($factories[ConfigInterface::class], SessionConfigFactory::class);
        self::assertSame($factories[ManagerInterface::class], SessionManagerFactory::class);
        self::assertSame($factories[StorageInterface::class], StorageFactory::class);
    }

    public function testProvidesAliasesForLegacyZendFrameworkClasses(): void
    {
        $config = $this->config['dependencies']['aliases'];

        self::assertSame($config[LegacySessionManager::class], SessionManager::class);
        self::assertSame($config[LegacyConfigInterface::class], ConfigInterface::class);
        self::assertSame($config[LegacyManagerInterface::class], ManagerInterface::class);
        self::assertSame($config[LegacyStorageInterface::class], StorageInterface::class);
    }
}
