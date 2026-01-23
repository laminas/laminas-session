<?php

declare(strict_types=1);

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Container;
use Laminas\Session\Exception\SessionValidationFailedException;
use Laminas\Session\ManagerInterface;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\Service\RemoteAddressFactory;
use Laminas\Session\Service\SessionManagerFactory;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\Storage\StorageInterface;
use Laminas\Session\Validator;
use Laminas\Session\Validator\Environment;
use Laminas\Session\Validator\HttpUserAgent;
use Laminas\Session\Validator\Id;
use Laminas\Session\Validator\RemoteAddr;
use LaminasTest\Session\ReflectionPropertyTrait;
use LaminasTest\Session\TestAsset\TestManager;
use LaminasTest\Session\TestAsset\TestSaveHandler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

use function serialize;

#[CoversClass(SessionManagerFactory::class)]
final class SessionManagerFactoryTest extends TestCase
{
    use ReflectionPropertyTrait;

    private ServiceManager $services;

    protected function setUp(): void
    {
        $this->services = new ServiceManager([
            'factories' => [
                ManagerInterface::class => SessionManagerFactory::class,
                TestManager::class      => SessionManagerFactory::class,
                TestSaveHandler::class  => SessionManagerFactory::class,
                Id::class               => InvokableFactory::class,
                HttpUserAgent::class    => InvokableFactory::class,
                RemoteAddr::class       => RemoteAddressFactory::class,
            ],
        ]);
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

    public function testAttachDefaultValidatorsByDefault(): void
    {
        $manager = $this->services->get(ManagerInterface::class);
        $value   = $this->getReflectionProperty($manager, 'validators');
        $this->assertIsArray($value);
        $this->assertInstanceOf(Id::class, $value[0]);
    }

    #[IgnoreDeprecations]
    #[RunInSeparateProcess]
    public function testFactoryWillAddValidatorViaConfiguration(): void
    {
        $config = [
            'session_manager' => [
                'validators' => [
                    'classes' => [
                        Validator\RemoteAddr::class,
                    ],
                ],
            ],
        ];

        $this->services->setService('config', $config);
        $manager = $this->services->get(ManagerInterface::class);
        self::assertInstanceOf(ManagerInterface::class, $manager);
        $storage = $manager->getStorage();
        $storage->setMetadata(
            'environment',
            serialize(new Environment(remoteAddr: 'invalid data'))
        );

        self::expectException(SessionValidationFailedException::class);
        self::expectExceptionMessage('Remote address validation failed');
        $manager->start();
    }

    #[RunInSeparateProcess]
    #[IgnoreDeprecations]
    public function testStartingSessionManagerFromFactoryDoesNotTriggerUndefinedVariable(): void
    {
        $storage = new ArrayStorage();
        $this->services->setService(StorageInterface::class, $storage);

        $manager = $this->services->get(ManagerInterface::class);
        $manager->start();

        self::assertSame($storage, $manager->getStorage());
    }

    #[RunInSeparateProcess]
    #[IgnoreDeprecations]
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
                        'classes' => [
                            Validator\HttpUserAgent::class,
                            Validator\RemoteAddr::class,
                        ],
                    ],
                ],
            ],
        );

        // This call is needed to make sure session storage data is not overwritten by the factory
        $manager = $this->services->get(ManagerInterface::class);

        $validatorData = $storage->getMetaData('_VALID');
        self::assertSame('Foo', $validatorData[Validator\HttpUserAgent::class]);
        self::assertSame('1.2.3.4', $validatorData[Validator\RemoteAddr::class]);
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
