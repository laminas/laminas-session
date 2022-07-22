<?php

declare(strict_types=1);

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Service\SessionConfigFactory;
use LaminasTest\Session\TestAsset\TestConfig;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Service\SessionConfigFactory
 */
class SessionConfigFactoryTest extends TestCase
{
    private ServiceManager $services;

    protected function setUp(): void
    {
        $config         = new Config(
            [
                'factories' => [
                    ConfigInterface::class => SessionConfigFactory::class,
                ],
            ]
        );
        $this->services = new ServiceManager();
        $config->configureServiceManager($this->services);
    }

    public function testCreatesSessionConfigByDefault(): void
    {
        $this->services->setService(
            'config',
            [
                'session_config' => [],
            ]
        );
        $config = $this->services->get(ConfigInterface::class);
        self::assertInstanceOf(SessionConfig::class, $config);
    }

    public function testCanCreateAlternateSessionConfigTypeViaConfigClassKey(): void
    {
        $this->services->setService(
            'config',
            [
                'session_config' => [
                    'config_class' => StandardConfig::class,
                ],
            ]
        );
        $config = $this->services->get(ConfigInterface::class);
        self::assertInstanceOf(StandardConfig::class, $config);
        // Since SessionConfig extends StandardConfig, need to assert not SessionConfig
        self::assertNotInstanceOf(SessionConfig::class, $config);
    }

    public function testServiceReceivesConfiguration(): void
    {
        $this->services->setService(
            'config',
            [
                'session_config' => [
                    'config_class' => StandardConfig::class,
                    'name'         => 'laminas',
                ],
            ]
        );
        $config = $this->services->get(ConfigInterface::class);
        self::assertEquals('laminas', $config->getName());
    }

    public function testServiceNotCreatedWhenInvalidSamesiteConfig()
    {
        $this->services->setService(
            'config',
            [
                'session_config' => [
                    'config_class'    => TestConfig::class,
                    'cookie_samesite' => 'Lax',
                ],
            ]
        );
        $this->expectException(ServiceNotCreatedException::class);
        $this->expectExceptionMessage('"cookie_samesite"');
        $this->services->get(ConfigInterface::class);
    }
}
