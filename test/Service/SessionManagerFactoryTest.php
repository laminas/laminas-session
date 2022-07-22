<?php

declare(strict_types=1);

namespace LaminasTest\Session\Service;

use Laminas\EventManager\Test\EventListenerIntrospectionTrait;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Container;
use Laminas\Session\ManagerInterface;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\Service\SessionManagerFactory;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Storage\StorageInterface;
use Laminas\Session\Validator;
use LaminasTest\Session\ReflectionPropertyTrait;
use LaminasTest\Session\TestAsset\TestManager;
use LaminasTest\Session\TestAsset\TestSaveHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function iterator_to_array;

/**
 * @covers \Laminas\Session\Service\SessionManagerFactory
 */
class SessionManagerFactoryTest extends TestCase
{
    use EventListenerIntrospectionTrait;
    use ReflectionPropertyTrait;

    private \Laminas\ServiceManager\ServiceManager $services;

    protected function setUp(): void
    {
        $config         = new Config(
            [
                'factories' => [
                    ManagerInterface::class => SessionManagerFactory::class,
                    TestManager::class      => SessionManagerFactory::class,
                    TestSaveHandler::class  => SessionManagerFactory::class,
                ],
            ]
        );
        $this->services = new ServiceManager();
        $config->configureServiceManager($this->services);
    }

    public function testCreatesSessionManager(): void
    {
        $manager = $this->services->get(ManagerInterface::class);
        self::assertInstanceOf(SessionManager::class, $manager);
    }

    public function testConfigObjectIsInjectedIfPresentInServices(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $this->services->setService(ConfigInterface::class, $config);
        $manager = $this->services->get(ManagerInterface::class);
        $test    = $manager->getConfig();
        self::assertSame($config, $test);
    }

    public function testFactoryWillInjectStorageIfPresentInServices(): void
    {
        // Using concrete version here as mocking was too complex
        $storage = new ArrayStorage();
        $this->services->setService(StorageInterface::class, $storage);
        $manager = $this->services->get(ManagerInterface::class);
        $test    = $manager->getStorage();
        self::assertSame($storage, $test);
    }

    public function testFactoryWillInjectSaveHandlerIfPresentInServices(): void
    {
        $saveHandler = $this->createMock(SaveHandlerInterface::class);
        $this->services->setService(SaveHandlerInterface::class, $saveHandler);
        $manager = $this->services->get(ManagerInterface::class);
        $test    = $manager->getSaveHandler();
        self::assertSame($saveHandler, $test);
    }

    public function testFactoryWillMarkManagerAsContainerDefaultByDefault(): void
    {
        $manager = $this->services->get(ManagerInterface::class);
        self::assertSame($manager, Container::getDefaultManager());
    }

    public function testCanDisableContainerDefaultManagerInjectionViaConfiguration(): void
    {
        $config = [
            'session_manager' => [
                'enable_default_container_manager' => false,
            ],
        ];
        $this->services->setService('config', $config);
        $manager = $this->services->get(ManagerInterface::class);
        self::assertNotSame($manager, Container::getDefaultManager());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryWillAddValidatorViaConfiguration(): void
    {
        $config = [
            'session_manager' => [
                'validators' => [
                    Validator\RemoteAddr::class,
                ],
            ],
        ];
        $this->services->setService('config', $config);
        $manager = $this->services->get(ManagerInterface::class);

        $manager->start();

        $chain     = $manager->getValidatorChain();
        $listeners = iterator_to_array($this->getListenersForEvent('session.validate', $chain));
        self::assertCount(2, $listeners);
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartingSessionManagerFromFactoryDoesNotTriggerUndefinedVariable(): void
    {
        $storage = new ArrayStorage();
        $this->services->setService(StorageInterface::class, $storage);

        $manager = $this->services->get(ManagerInterface::class);
        $manager->start();

        self::assertSame($storage, $manager->getStorage());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryDoesNotOverwriteValidatorStorageValues(): void
    {
        $storage = new ArrayStorage();
        $storage->setMetadata(
            '_VALID',
            [
                Validator\HttpUserAgent::class => 'Foo',
                Validator\RemoteAddr::class    => '1.2.3.4',
            ]
        );
        $this->services->setService(StorageInterface::class, $storage);
        $this->services->setService(
            'config',
            [
                'session_manager' => [
                    'validators' => [
                        Validator\HttpUserAgent::class,
                        Validator\RemoteAddr::class,
                    ],
                ],
            ]
        );

        // This call is needed to make sure session storage data is not overwritten by the factory
        $manager = $this->services->get(ManagerInterface::class);

        $validatorData = $storage->getMetaData('_VALID');
        self::assertSame('Foo', $validatorData[Validator\HttpUserAgent::class]);
        self::assertSame('1.2.3.4', $validatorData[Validator\RemoteAddr::class]);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryDoesNotAttachValidatorTwoTimes(): void
    {
        $storage = new ArrayStorage();
        $storage->setMetadata(
            '_VALID',
            [
                Validator\RemoteAddr::class => '1.2.3.4',
            ]
        );
        $this->services->setService(StorageInterface::class, $storage);
        $this->services->setService(
            'config',
            [
                'session_manager' => [
                    'validators' => [
                        Validator\RemoteAddr::class,
                    ],
                ],
            ]
        );

        $manager = $this->services->get(ManagerInterface::class);
        try {
            $manager->start();
        } catch (RuntimeException $e) {
            // Ignore exception, because we are not interested whether session validation passes in this test
        }

        $chain     = $manager->getValidatorChain();
        $listeners = iterator_to_array($this->getListenersForEvent('session.validate', $chain));
        self::assertCount(2, $listeners);

        $found = false;
        foreach ($listeners as $listener) {
            // Listeners are all [$validator, 'isValid'] callbacks
            if ($listener[0] instanceof Validator\RemoteAddr) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'Did not find RemoteAddr validator in listeners');
    }

    public function testFactoryAllowsOverridingOptions(): void
    {
        $storage = new ArrayStorage();
        $this->services->setService(StorageInterface::class, $storage);
        $this->services->setService(
            'config',
            [
                'session_manager' => [
                    'options' => [
                        'attach_default_validators' => false,
                    ],
                ],
            ]
        );

        $manager = $this->services->get(ManagerInterface::class);

        $containedValidators = $this->getReflectionProperty($manager, 'validators');
        self::assertSame([], $containedValidators);
    }

    public function testFactoryWillUseRequestedNameAsSessionManagerIfItImplementsManagerInterface(): void
    {
        $manager = $this->services->get(TestManager::class);
        self::assertInstanceOf(TestManager::class, $manager);
    }

    public function testFactoryWillRaiseServiceNotCreatedExceptionIfRequestedNameIsNotAManagerInterfaceSubclass(): void
    {
        $this->expectException(ServiceNotCreatedException::class);
        $manager = $this->services->get(TestSaveHandler::class);
    }
}
