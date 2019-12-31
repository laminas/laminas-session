<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\ServiceManager;

/**
 * @group      Laminas_Session
 */
class StorageFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('Laminas\Session\Storage\StorageInterface', 'Laminas\Session\Service\StorageFactory');
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
            ], 'Laminas\Session\Storage\ArrayStorage'],
            'array-storage-fqcn' => [[
                'session_storage' => [
                    'type' => 'Laminas\Session\Storage\ArrayStorage',
                    'options' => [
                        'input' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ], 'Laminas\Session\Storage\ArrayStorage'],
            'session-array-storage-short' => [[
                'session_storage' => [
                    'type' => 'SessionArrayStorage',
                    'options' => [
                        'input' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ], 'Laminas\Session\Storage\SessionArrayStorage'],
            'session-array-storage-fqcn' => [[
                'session_storage' => [
                    'type' => 'Laminas\Session\Storage\SessionArrayStorage',
                    'options' => [
                        'input' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ], 'Laminas\Session\Storage\SessionArrayStorage'],
        ];
    }

    /**
     * @dataProvider sessionStorageConfig
     */
    public function testUsesConfigurationToCreateStorage($config, $class)
    {
        $this->services->setService('Config', $config);
        $storage = $this->services->get('Laminas\Session\Storage\StorageInterface');
        $this->assertInstanceOf($class, $storage);
        $test = $storage->toArray();
        $this->assertEquals($config['session_storage']['options']['input'], $test);
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
        $this->services->setService('Config', $config);
        $this->setExpectedException('Laminas\ServiceManager\Exception\ServiceNotCreatedException');
        $storage = $this->services->get('Laminas\Session\Storage\StorageInterface');
    }
}
