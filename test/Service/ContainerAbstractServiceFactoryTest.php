<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session\Service;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Session\Container;
use Zend\Session\ManagerInterface;
use Zend\Session\Service\ContainerAbstractServiceFactory;
use Zend\Session\Service\SessionManagerFactory;
use Zend\Session\Storage\ArrayStorage;
use Zend\Session\Storage\StorageInterface;

/**
 * @covers \Zend\Session\Service\ContainerAbstractServiceFactory
 */
class ContainerAbstractServiceFactoryTest extends TestCase
{
    public $config = [
        'session_containers' => [
            'foo',
            'bar',
            'Baz\Bat',
            'Underscore_Separated',
            'With\Digits_0123',
        ],
    ];

    protected function setUp()
    {
        $config = new Config([
            'services' => [
                'config' => $this->config,
                StorageInterface::class => new ArrayStorage(),
            ],
            'factories' => [
                ManagerInterface::class => SessionManagerFactory::class,
            ],
            'abstract_factories' => [
                ContainerAbstractServiceFactory::class,
            ],
        ]);
        $this->services = new ServiceManager();
        $config->configureServiceManager($this->services);
    }

    public function validContainers()
    {
        $containers = [];
        $config     = $this->config;
        foreach ($config['session_containers'] as $name) {
            $containers[] = [$name, $name];
        }

        return $containers;
    }

    /**
     * @dataProvider validContainers
     */
    public function testCanRetrieveNamedContainers($serviceName, $containerName)
    {
        $this->assertTrue($this->services->has($serviceName), "Container does not have service by name '$serviceName'");
        $container = $this->services->get($serviceName);
        $this->assertInstanceOf(Container::class, $container);
        $this->assertEquals($containerName, $container->getName());
    }

    /**
     * @dataProvider validContainers
     */
    public function testContainersAreInjectedWithSessionManagerService($serviceName, $containerName)
    {
        $this->assertTrue($this->services->has($serviceName), "Container does not have service by name '$serviceName'");
        $container = $this->services->get($serviceName);
        $this->assertSame($this->services->get(ManagerInterface::class), $container->getManager());
    }

    public function invalidContainers()
    {
        $containers = [];
        $config = $this->config;
        foreach ($config['session_containers'] as $name) {
            $containers[] = ['SomePrefix\\' . $name];
        }
        $containers[] = ['DOES_NOT_EXIST'];

        return $containers;
    }

    /**
     * @dataProvider invalidContainers
     */
    public function testInvalidContainerNamesAreNotMatchedByAbstractFactory($name)
    {
        $this->assertFalse($this->services->has($name));
    }
}
