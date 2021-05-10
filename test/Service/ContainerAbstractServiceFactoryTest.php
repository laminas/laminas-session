<?php

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Container;
use Laminas\Session\ManagerInterface;
use Laminas\Session\Service\ContainerAbstractServiceFactory;
use Laminas\Session\Service\SessionManagerFactory;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Service\ContainerAbstractServiceFactory
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

    protected function setUp(): void
    {
        $config         = new Config(
            [
                'services'           => [
                    'config'                => $this->config,
                    StorageInterface::class => new ArrayStorage(),
                ],
                'factories'          => [
                    ManagerInterface::class => SessionManagerFactory::class,
                ],
                'abstract_factories' => [
                    ContainerAbstractServiceFactory::class,
                ],
            ]
        );
        $this->services = new ServiceManager();
        $config->configureServiceManager($this->services);
    }

    public function validContainers(): array
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
    public function testCanRetrieveNamedContainers($serviceName, $containerName): void
    {
        self::assertTrue($this->services->has($serviceName), "Container does not have service by name '$serviceName'");
        $container = $this->services->get($serviceName);
        self::assertInstanceOf(Container::class, $container);
        self::assertEquals($containerName, $container->getName());
    }

    /**
     * @dataProvider validContainers
     */
    public function testContainersAreInjectedWithSessionManagerService($serviceName, $containerName): void
    {
        self::assertTrue($this->services->has($serviceName), "Container does not have service by name '$serviceName'");
        $container = $this->services->get($serviceName);
        self::assertSame($this->services->get(ManagerInterface::class), $container->getManager());
    }

    public function invalidContainers(): array
    {
        $containers = [];
        $config     = $this->config;
        foreach ($config['session_containers'] as $name) {
            $containers[] = ['SomePrefix\\' . $name];
        }
        $containers[] = ['DOES_NOT_EXIST'];

        return $containers;
    }

    /**
     * @dataProvider invalidContainers
     */
    public function testInvalidContainerNamesAreNotMatchedByAbstractFactory($name): void
    {
        self::assertFalse($this->services->has($name));
    }
}
