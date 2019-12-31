<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Storage\ArrayStorage;

/**
 * @group      Laminas_Session
 */
class ContainerAbstractServiceFactoryTest extends \PHPUnit_Framework_TestCase
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

    public function setUp()
    {
        $this->services = new ServiceManager();

        $this->services->setService('Laminas\Session\Storage\StorageInterface', new ArrayStorage());
        $this->services->setFactory('Laminas\Session\ManagerInterface', 'Laminas\Session\Service\SessionManagerFactory');
        $this->services->addAbstractFactory('Laminas\Session\Service\ContainerAbstractServiceFactory');

        $this->services->setService('Config', $this->config);
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
        $this->assertInstanceOf('Laminas\Session\Container', $container);
        $this->assertEquals($containerName, $container->getName());
    }

    /**
     * @dataProvider validContainers
     */
    public function testContainersAreInjectedWithSessionManagerService($serviceName, $containerName)
    {
        $this->assertTrue($this->services->has($serviceName), "Container does not have service by name '$serviceName'");
        $container = $this->services->get($serviceName);
        $this->assertSame($this->services->get('Laminas\Session\ManagerInterface'), $container->getManager());
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
