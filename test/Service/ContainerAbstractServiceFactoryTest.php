<?php

declare(strict_types=1);

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
    /** @var array{session_containers: list<string>} */
    private array $config = [
        'session_containers' => [
            'foo',
            'bar',
            'Baz\Bat',
            'Underscore_Separated',
            'With\Digits_0123',
        ],
    ];

    private ServiceManager $services;

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
        $this->services = new ServiceManager($config);
        $config->configureServiceManager($this->services);
    }

    /** @psalm-return array<array-key, array{0: string, 1: string}> */
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
    public function testCanRetrieveNamedContainers(string $serviceName, string $containerName): void
    {
        self::assertTrue($this->services->has($serviceName), "Container does not have service by name '$serviceName'");
        $container = $this->services->get($serviceName);
        self::assertInstanceOf(Container::class, $container);
        self::assertEquals($containerName, $container->getName());
    }

    /**
     * @dataProvider validContainers
     */
    public function testContainersAreInjectedWithSessionManagerService(string $serviceName, string $containerName): void
    {
        self::assertTrue($this->services->has($serviceName), "Container does not have service by name '$serviceName'");
        $container = $this->services->get($serviceName);
        self::assertSame($this->services->get(ManagerInterface::class), $container->getManager());
    }

    /** @psalm-return array<array-key, array{0: string}> */
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
    public function testInvalidContainerNamesAreNotMatchedByAbstractFactory(string $name): void
    {
        self::assertFalse($this->services->has($name));
    }
}
