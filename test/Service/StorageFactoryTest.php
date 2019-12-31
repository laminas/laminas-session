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
        return array(
            'array-storage-short' => array(array(
                'session_storage' => array(
                    'type' => 'ArrayStorage',
                    'options' => array(
                        'input' => array(
                            'foo' => 'bar',
                        ),
                    ),
                ),
            ), 'Laminas\Session\Storage\ArrayStorage'),
            'array-storage-fqcn' => array(array(
                'session_storage' => array(
                    'type' => 'Laminas\Session\Storage\ArrayStorage',
                    'options' => array(
                        'input' => array(
                            'foo' => 'bar',
                        ),
                    ),
                ),
            ), 'Laminas\Session\Storage\ArrayStorage'),
            'session-array-storage-short' => array(array(
                'session_storage' => array(
                    'type' => 'SessionArrayStorage',
                    'options' => array(
                        'input' => array(
                            'foo' => 'bar',
                        ),
                    ),
                ),
            ), 'Laminas\Session\Storage\SessionArrayStorage'),
            'session-array-storage-fqcn' => array(array(
                'session_storage' => array(
                    'type' => 'Laminas\Session\Storage\SessionArrayStorage',
                    'options' => array(
                        'input' => array(
                            'foo' => 'bar',
                        ),
                    ),
                ),
            ), 'Laminas\Session\Storage\SessionArrayStorage'),
        );
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
        return array(
            'unknown-class-short' => array(array(
                'session_storage' => array(
                    'type' => 'FooStorage',
                    'options' => array(),
                ),
            )),
            'unknown-class-fqcn' => array(array(
                'session_storage' => array(
                    'type' => 'Foo\Bar\Baz\Bat',
                    'options' => array(),
                ),
            )),
            'bad-class' => array(array(
                'session_storage' => array(
                    'type' => 'Laminas\Session\Config\StandardConfig',
                    'options' => array(),
                ),
            )),
            'good-class-invalid-options' => array(array(
                'session_storage' => array(
                    'type' => 'ArrayStorage',
                    'options' => array(
                        'input' => 'this is invalid',
                    ),
                ),
            )),
        );
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
