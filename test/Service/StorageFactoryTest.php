<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Service;

use ArrayObject;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Service\StorageFactory;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Service\StorageFactory
 */
class StorageFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $config         = new Config(
            [
                'factories' => [
                    StorageInterface::class => StorageFactory::class,
                ],
            ]
        );
        $this->services = new ServiceManager();
        $config->configureServiceManager($this->services);
    }

    public function sessionStorageConfig()
    {
        return [
            'array-storage-short' => [[
                'session_storage' => [
                    'type' => 'ArrayStorage',
                    'options' => [
                        'input' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ], ArrayStorage::class],
            'array-storage-fqcn' => [[
                'session_storage' => [
                    'type' => ArrayStorage::class,
                    'options' => [
                        'input' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ], ArrayStorage::class],
            'session-array-storage-short' => [[
                'session_storage' => [
                    'type' => 'SessionArrayStorage',
                    'options' => [
                        'input' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ], SessionArrayStorage::class],
            'session-array-storage-arrayobject' => [[
                'session_storage' => [
                    'type' => 'SessionArrayStorage',
                    'options' => [
                        'input' => new ArrayObject([
                            'foo' => 'bar',
                        ]),
                    ],
                ],
            ], SessionArrayStorage::class],
            'session-array-storage-fqcn' => [[
                'session_storage' => [
                    'type' => SessionArrayStorage::class,
                    'options' => [
                        'input' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ], SessionArrayStorage::class],
        ];
    }

    /**
     * @dataProvider sessionStorageConfig
     */
    public function testUsesConfigurationToCreateStorage($config, $class)
    {
        $this->services->setService('config', $config);
        $storage = $this->services->get(StorageInterface::class);
        self::assertInstanceOf($class, $storage);
        $test = $storage->toArray();
        self::assertEquals($config['session_storage']['options']['input'], $test);
    }

    public function testConfigurationWithoutInputIsValid()
    {
        $this->services->setService(
            'config', [
            'session_storage' => [
                'type'    => ArrayStorage::class,
                'options' => [],
            ],
        ]
        );

        $storage = $this->services->get(StorageInterface::class);

        self::assertInstanceOf(ArrayStorage::class, $storage);
        self::assertSame([], $storage->toArray());
    }

    public function invalidSessionStorageConfig()
    {
        return [
            'unknown-class-short' => [[
                'session_storage' => [
                    'type' => 'FooStorage',
                    'options' => [],
                ],
            ]],
            'unknown-class-fqcn' => [[
                'session_storage' => [
                    'type' => 'Foo\Bar\Baz\Bat',
                    'options' => [],
                ],
            ]],
            'bad-class' => [[
                'session_storage' => [
                    'type' => 'Laminas\Session\Config\StandardConfig',
                    'options' => [],
                ],
            ]],
            'good-class-invalid-options' => [[
                'session_storage' => [
                    'type' => 'ArrayStorage',
                    'options' => [
                        'input' => 'this is invalid',
                    ],
                ],
            ]],
        ];
    }

    /**
     * @dataProvider invalidSessionStorageConfig
     */
    public function testInvalidConfigurationRaisesServiceNotCreatedException($config)
    {
        $this->services->setService('config', $config);
        $this->expectException(ServiceNotCreatedException::class);
        $storage = $this->services->get(StorageInterface::class);
    }
}
