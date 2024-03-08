<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use ArrayObject;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\Exception\InvalidArgumentException;
use Laminas\Session\ManagerInterface as Manager;
use Laminas\Session\Storage\SessionArrayStorage;
use PHPUnit\Framework\TestCase;

use function microtime;
use function sleep;
use function time;

/**
 * @covers \Laminas\Session\Container
 * @covers \Laminas\Session\AbstractContainer
 */
class ContainerTest extends TestCase
{
    /**
     * Hack to allow running tests in separate processes
     *
     * @see http://matthewturland.com/2010/08/19/process-isolation-in-phpunit/
     *
     * @var bool
     */
    protected $preserveGlobalState = false;

    /** @var Manager */
    protected $manager;

    /** @var Container */
    protected $container;

    protected function setUp(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);

        $config = new StandardConfig();

        $this->manager   = $manager = new TestAsset\TestManager($config);
        $this->container = new Container('Default', $manager);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);
    }

    public function testInstantiationStartsSession(): void
    {
        $this->manager->destroy();
        $container = new Container('Default', $this->manager);
        self::assertTrue($this->manager->started);
    }

    public function testInstantiatingContainerWithoutNameUsesDefaultAsName(): void
    {
        self::assertEquals('Default', $this->container->getName());
    }

    public function testPassingNameToConstructorInstantiatesContainerWithThatName(): void
    {
        $container = new Container('foo', $this->manager);
        self::assertEquals('foo', $container->getName());
    }

    public function testPassingNameStartingWithDigitToConstructorInstantiatesContainerWithThatName(): void
    {
        $container = new Container('0foo', $this->manager);
        self::assertEquals('0foo', $container->getName());
    }

    public function testUsingOldLaminas1NameIsStillValid(): void
    {
        $container = new Container('Laminas_Foo', $this->manager);
        self::assertEquals('Laminas_Foo', $container->getName());
    }

    public function testUsingNewLaminasNamespaceIsValid(): void
    {
        $container = new Container('Laminas\Foo', $this->manager);
        self::assertEquals('Laminas\Foo', $container->getName());
    }

    public function testPassingInvalidNameToConstructorRaisesException(): void
    {
        $tries = [
            'f!',
            'foo bar',
            '_foo',
            '__foo',
            '\foo',
            '\\foo',
        ];
        foreach ($tries as $try) {
            try {
                $container = new Container($try);
                self::fail('Invalid container name should raise exception');
            } catch (InvalidArgumentException $e) {
                self::assertStringContainsString('invalid', $e->getMessage());
            }
        }
    }

    public function testContainerActsAsArray(): void
    {
        $this->container['foo'] = 'bar';
        self::assertTrue(isset($this->container['foo']));
        self::assertEquals('bar', $this->container['foo']);
        unset($this->container['foo']);
        self::assertFalse(isset($this->container['foo']));
    }

    public function testContainerActsAsObject(): void
    {
        $this->container->foo = 'bar';
        self::assertTrue(isset($this->container->foo));
        self::assertEquals('bar', $this->container->foo);
        unset($this->container->foo);
        self::assertFalse(isset($this->container->foo));
    }

    public function testDefaultManagerIsAlwaysPopulated(): void
    {
        $manager = Container::getDefaultManager();
        self::assertInstanceOf(Manager::class, $manager);
    }

    public function testCanSetDefaultManager(): void
    {
        $manager = new TestAsset\TestManager();
        Container::setDefaultManager($manager);
        self::assertSame($manager, Container::getDefaultManager());
    }

    public function testCanSetDefaultManagerToNull(): void
    {
        $manager = new TestAsset\TestManager();
        Container::setDefaultManager($manager);
        Container::setDefaultManager(null);
        self::assertNotSame($manager, Container::getDefaultManager());
    }

    public function testDefaultManagerUsedWhenNoManagerProvided(): void
    {
        $manager   = Container::getDefaultManager();
        $container = new Container();
        self::assertSame($manager, $container->getManager());
    }

    public function testContainerInstantiatesManagerWithDefaultsWhenNotInjected(): void
    {
        $container = new Container();
        $manager   = $container->getManager();
        self::assertInstanceOf(Manager::class, $manager);
        $config = $manager->getConfig();
        self::assertInstanceOf(SessionConfig::class, $config);
        $storage = $manager->getStorage();
        self::assertInstanceOf(SessionArrayStorage::class, $storage);
    }

    public function testContainerAllowsInjectingManagerViaConstructor(): void
    {
        $config    = new StandardConfig();
        $manager   = new TestAsset\TestManager($config);
        $container = new Container('Foo', $manager);
        self::assertSame($manager, $container->getManager());
    }

    public function testContainerWritesToStorage(): void
    {
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        self::assertTrue(isset($storage['Default']));
        self::assertTrue(isset($storage['Default']['foo']));
        self::assertEquals('bar', $storage['Default']['foo']);

        unset($this->container->foo);
        self::assertFalse(isset($storage['Default']['foo']));
    }

    public function testSettingExpirationSecondsUpdatesStorageMetadataForFullContainer(): void
    {
        $currentTimestamp = time();
        $this->container->setExpirationSeconds(3600);
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertArrayHasKey('EXPIRE', $metadata);
        self::assertEquals($currentTimestamp + 3600, $metadata['EXPIRE']);
    }

    public function testSettingExpirationSecondsForIndividualKeyUpdatesStorageMetadataForThatKey(): void
    {
        $this->container->foo = 'bar';
        $currentTimestamp     = time();
        $this->container->setExpirationSeconds(3600, 'foo');
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertArrayHasKey('EXPIRE_KEYS', $metadata);
        self::assertArrayHasKey('foo', $metadata['EXPIRE_KEYS']);
        self::assertEquals($currentTimestamp + 3600, $metadata['EXPIRE_KEYS']['foo']);
    }

    public function testMultipleCallsToExpirationSecondsAggregates(): void
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->baz = 'bat';
        $this->container->bat = 'bas';
        $currentTimestamp     = time();
        $this->container->setExpirationSeconds(3600);
        $this->container->setExpirationSeconds(1800, 'foo');
        $this->container->setExpirationSeconds(900, ['baz', 'bat']);
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertEquals($currentTimestamp + 1800, $metadata['EXPIRE_KEYS']['foo']);
        self::assertEquals($currentTimestamp + 900, $metadata['EXPIRE_KEYS']['baz']);
        self::assertEquals($currentTimestamp + 900, $metadata['EXPIRE_KEYS']['bat']);
        self::assertEquals($currentTimestamp + 3600, $metadata['EXPIRE']);
    }

    public function testSettingExpirationSecondsUsesCurrentTime(): void
    {
        sleep(3);
        $this->container->setExpirationSeconds(2);
        $this->container->foo = 'bar';

        // Simulate a second request: overwrite the request time with current time()
        $_SERVER['REQUEST_TIME']       = time();
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

        self::assertEquals('bar', $this->container->foo);
    }

    public function testPassingUnsetKeyToSetExpirationSecondsDoesNothing(): void
    {
        $this->container->setExpirationSeconds(3600, 'foo');
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testPassingUnsetKeyInArrayToSetExpirationSecondsDoesNothing(): void
    {
        $this->container->setExpirationSeconds(3600, ['foo']);
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testGetKeyWithContainerExpirationInPastResetsToNull(): void
    {
        self::assertIsInt($_SERVER['REQUEST_TIME']);
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        self::assertNull($this->container->foo);
    }

    public function testGetKeyWithExpirationInPastResetsToNull(): void
    {
        self::assertIsInt($_SERVER['REQUEST_TIME']);
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE_KEYS' => ['foo' => $_SERVER['REQUEST_TIME'] - 18600]]);
        self::assertNull($this->container->foo);
        self::assertEquals('baz', $this->container->bar);
    }

    public function testKeyExistsWithContainerExpirationInPastReturnsFalse(): void
    {
        self::assertIsInt($_SERVER['REQUEST_TIME']);
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        self::assertFalse(isset($this->container->foo));
    }

    public function testKeyExistsWithExpirationInPastReturnsFalse(): void
    {
        self::assertIsInt($_SERVER['REQUEST_TIME']);
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE_KEYS' => ['foo' => $_SERVER['REQUEST_TIME'] - 18600]]);
        self::assertFalse(isset($this->container->foo));
        self::assertTrue(isset($this->container->bar));
    }

    public function testKeyExistsWithContainerExpirationInPastWithSetExpirationSecondsReturnsFalse(): void
    {
        self::assertIsInt($_SERVER['REQUEST_TIME']);
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        $this->container->setExpirationSeconds(1);
        self::assertFalse(isset($this->container->foo));
    }

    public function testSettingExpiredKeyOverwritesExpiryMetadataForThatKey(): void
    {
        self::assertIsInt($_SERVER['REQUEST_TIME']);
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        $this->container->foo = 'baz';
        self::assertTrue(isset($this->container->foo));
        self::assertEquals('baz', $this->container->foo);
        $metadata = $storage->getMetadata('Default');
        self::assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testSettingExpirationHopsWithNoVariablesMarksContainerByWritingToStorage(): void
    {
        $this->container->setExpirationHops(2);
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata('Default');
        self::assertArrayHasKey('EXPIRE_HOPS', $metadata);
        self::assertEquals(
            ['hops' => 2, 'ts' => $storage->getRequestAccessTime()],
            $metadata['EXPIRE_HOPS']
        );
    }

    public function testSettingExpirationHopsWithSingleKeyMarksContainerByWritingToStorage(): void
    {
        $this->container->foo = 'bar';
        $this->container->setExpirationHops(2, 'foo');
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata('Default');
        self::assertArrayHasKey('EXPIRE_HOPS_KEYS', $metadata);
        self::assertArrayHasKey('foo', $metadata['EXPIRE_HOPS_KEYS']);
        self::assertEquals(
            ['hops' => 2, 'ts' => $storage->getRequestAccessTime()],
            $metadata['EXPIRE_HOPS_KEYS']['foo']
        );
    }

    public function testSettingExpirationHopsWithMultipleKeysMarksContainerByWritingToStorage(): void
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->baz = 'bat';
        $this->container->setExpirationHops(2, ['foo', 'baz']);
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata('Default');
        self::assertArrayHasKey('EXPIRE_HOPS_KEYS', $metadata);

        $hops     = $metadata['EXPIRE_HOPS_KEYS'];
        $ts       = $storage->getRequestAccessTime();
        $expected = [
            'foo' => [
                'hops' => 2,
                'ts'   => $ts,
            ],
            'baz' => [
                'hops' => 2,
                'ts'   => $ts,
            ],
        ];
        self::assertEquals($expected, $hops);
    }

    public function testContainerExpiresAfterSpecifiedHops(): void
    {
        $this->container->foo = 'bar';
        $this->container->setExpirationHops(1);

        $storage = $this->manager->getStorage();
        $ts      = $storage->getRequestAccessTime();

        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 60);
        self::assertEquals('bar', $this->container->foo);

        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 120);
        self::assertNull($this->container->foo);
    }

    public function testInstantiatingMultipleContainersInSameRequestDoesNotCreateExtraHops(): void
    {
        $this->container->foo = 'bar';
        $this->container->setExpirationHops(1);

        $container = new Container('Default', $this->manager);
        self::assertEquals('bar', $container->foo);
        self::assertEquals('bar', $this->container->foo);
    }

    public function testKeyExpiresAfterSpecifiedHops(): void
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->setExpirationHops(1, 'foo');

        $storage = $this->manager->getStorage();
        $ts      = $storage->getRequestAccessTime();

        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 60);
        self::assertEquals('bar', $this->container->foo);
        self::assertEquals('baz', $this->container->bar);

        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 120);
        self::assertNull($this->container->foo);
        self::assertEquals('baz', $this->container->bar);
    }

    public function testInstantiatingMultipleContainersInSameRequestDoesNotCreateExtraKeyHops(): void
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->setExpirationHops(1, 'foo');

        $container = new Container('Default', $this->manager);
        self::assertEquals('bar', $container->foo);
        self::assertEquals('bar', $this->container->foo);
        self::assertEquals('baz', $container->bar);
        self::assertEquals('baz', $this->container->bar);
    }

    public function testKeysExpireAfterSpecifiedHops(): void
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->baz = 'bat';
        $this->container->setExpirationHops(1, ['foo', 'baz']);

        $storage = $this->manager->getStorage();
        $ts      = $storage->getRequestAccessTime();

        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 60);
        self::assertEquals('bar', $this->container->foo);
        self::assertEquals('baz', $this->container->bar);
        self::assertEquals('bat', $this->container->baz);

        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 120);
        self::assertNull($this->container->foo);
        self::assertEquals('baz', $this->container->bar);
        self::assertNull($this->container->baz);
    }

    public function testCanIterateOverContainer(): void
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->baz = 'bat';
        $expected             = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        ];
        $test                 = [];
        foreach ($this->container as $key => $value) {
            $test[$key] = $value;
        }
        self::assertSame($expected, $test);
    }

    public function testIterationHonorsExpirationHops(): void
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $this->container->baz = 'bat';
        $this->container->setExpirationHops(1, ['foo', 'baz']);

        $storage = $this->manager->getStorage();
        $ts      = $storage->getRequestAccessTime();

        // First hop
        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 60);
        $expected = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
        ];
        $test     = [];
        foreach ($this->container as $key => $value) {
            $test[$key] = $value;
        }
        self::assertSame($expected, $test);

        // Second hop
        $storage->setMetadata('_REQUEST_ACCESS_TIME', $ts + 120);
        $expected = ['bar' => 'baz'];
        $test     = [];
        foreach ($this->container as $key => $value) {
            $test[$key] = $value;
        }
        self::assertSame($expected, $test);
    }

    public function testIterationHonorsExpirationTimestamps(): void
    {
        self::assertIsInt($_SERVER['REQUEST_TIME']);
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE_KEYS' => ['foo' => $_SERVER['REQUEST_TIME'] - 18600]]);
        $expected = ['bar' => 'baz'];
        $test     = [];
        foreach ($this->container as $key => $value) {
            $test[$key] = $value;
        }
        self::assertSame($expected, $test);
    }

    public function testValidationShouldNotRaiseErrorForMissingResponseObject(): void
    {
        $session       = new Container('test');
        $session->test = 42;
        self::assertEquals(42, $session->test);
    }

    public function testExchangeArray(): void
    {
        $this->container->offsetSet('old', 'old');
        self::assertTrue($this->container->offsetExists('old'));

        $old = $this->container->exchangeArray(['new' => 'new']);
        self::assertArrayHasKey('old', $old, "'exchangeArray' doesn't return an array of old items");
        self::assertFalse($this->container->offsetExists('old'), "'exchangeArray' doesn't remove old items");
        self::assertTrue($this->container->offsetExists('new'), "'exchangeArray' doesn't add the new array items");
    }

    public function testExchangeArrayObject(): void
    {
        $this->container->offsetSet('old', 'old');
        self::assertTrue($this->container->offsetExists('old'));

        $old = $this->container->exchangeArray(new \Laminas\Stdlib\ArrayObject(['new' => 'new']));
        self::assertArrayHasKey('old', $old, "'exchangeArray' doesn't return an array of old items");
        self::assertFalse($this->container->offsetExists('old'), "'exchangeArray' doesn't remove old items");
        self::assertTrue($this->container->offsetExists('new'), "'exchangeArray' doesn't add the new array items");
    }

    public function testMultiDimensionalUnset(): void
    {
        $this->container->foo = ['bar' => 'baz'];
        unset($this->container['foo']['bar']);
        self::assertSame([], $this->container->foo);
    }

    public function testUpgradeBehaviors(): void
    {
        $storage        = $this->manager->getStorage();
        $storage['foo'] = new ArrayObject(['bar' => 'baz']);

        $container = new Container('foo', $this->manager);
        self::assertEquals('baz', $container->bar);
        $container->baz = 'boo';
        self::assertEquals('boo', $storage['foo']['baz']);
    }

    public function testGetArrayCopyAfterExchangeArray(): void
    {
        $this->container->exchangeArray(['foo' => 'bar']);

        $contents = $this->container->getArrayCopy();

        self::assertIsArray($contents);
        self::assertArrayHasKey('foo', $contents, "'getArrayCopy' doesn't return exchanged array");
    }
}
