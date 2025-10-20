<?php

declare(strict_types=1);

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\Service\SessionConfigFactory;
use LaminasTest\Session\TestAsset\TestConfig;
use LaminasTest\Session\TestAsset\TestSaveHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function ini_get;
use function ini_set;
use function session_save_path;
use function session_status;
use function session_write_close;

use const PHP_SESSION_ACTIVE;

#[CoversClass(SessionConfigFactory::class)]
class SessionConfigFactoryTest extends TestCase
{
    private ServiceManager $services;

    private string|false $originalSaveHandler;

    private string|false $originalSavePath;

    protected function setUp(): void
    {
        $this->originalSaveHandler = ini_get('session.save_handler');
        $this->originalSavePath    = session_save_path();
        $this->services            = new ServiceManager([
            'factories' => [
                ConfigInterface::class => SessionConfigFactory::class,
            ],
        ]);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalSaveHandler !== false) {
            ini_set('session.save_handler', $this->originalSaveHandler);
        }
        if ($this->originalSavePath !== false && $this->originalSavePath !== '') {
            /** @psalm-suppress UnusedFunctionCall */
            session_save_path($this->originalSavePath);
        }
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

    public function testCanCreateSaveHandlerServiceAndPathViaConfig(): void
    {
        $saveHandler   = new TestSaveHandler();
        $savePath      = 'not_a_directory';
        $mockContainer = $this->getMockBuilder(ContainerInterface::class)
            ->onlyMethods(['get', 'has'])
            ->getMock();

        $mockContainer->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    'config',
                    [
                        'session_config' => [
                            'save_handler' => SaveHandlerInterface::class,
                            'save_path'    => $savePath,
                        ],
                    ],
                ],
                [
                    SaveHandlerInterface::class,
                    $saveHandler,
                ],
            ]);

        $mockContainer->expects(self::once())
            ->method('has')
            ->with(SaveHandlerInterface::class)
            ->willReturn(true);

        $factory = new SessionConfigFactory();
        /** @var SessionConfig $config */
        $config = $factory($mockContainer, ConfigInterface::class);

        self::assertInstanceOf(SessionConfig::class, $config);
        /** @noinspection PhpUndefinedMethodInspection This will always exist in SessionConfig */
        self::assertSame($saveHandler::class, $config->getSaveHandler());
        self::assertSame('user', ini_get('session.save_handler'));
        self::assertSame($savePath, session_save_path());
    }

    public function testCanCreateSaveHandlerAndPathViaConfig(): void
    {
        $saveHandler   = new TestSaveHandler();
        $savePath      = 'not_a_directory';
        $mockContainer = $this->getMockBuilder(ContainerInterface::class)
                              ->onlyMethods(['get', 'has'])
                              ->getMock();

        $mockContainer->expects(self::once())
                      ->method('get')
                      ->willReturnMap([
                          [
                              'config',
                              [
                                  'session_config' => [
                                      'save_handler' => TestSaveHandler::class,
                                      'save_path'    => $savePath,
                                  ],
                              ],
                          ],
                      ]);

        $factory = new SessionConfigFactory();
        $config  = $factory($mockContainer, ConfigInterface::class);

        self::assertInstanceOf(SessionConfig::class, $config);
        /** @noinspection PhpUndefinedMethodInspection This will always exist in SessionConfig */
        self::assertSame($saveHandler::class, $config->getSaveHandler());
        self::assertSame('user', ini_get('session.save_handler'));
        self::assertSame($savePath, session_save_path());
    }
}
