<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session;

use ArrayObject;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\Exception\InvalidArgumentException;
use Laminas\Session\ManagerInterface as Manager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Container
 */
class ContainerTest extends TestCase
{
    /**
     * Hack to allow running tests in separate processes
     *
     * @see    http://matthewturland.com/2010/08/19/process-isolation-in-phpunit/
     */
    protected $preserveGlobalState = false;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var Container
     */
    protected $container;

    protected function setUp(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);

        $config = new StandardConfig(
            [
                'storage' => 'Laminas\\Session\\Storage\\ArrayStorage',
            ]
        );

        $this->manager   = $manager = new TestAsset\TestManager($config);
        $this->container = new Container('Default', $manager);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);
    }

    public function testInstantiationStartsSession()
    {
        $this->manager->destroy();
        $container = new Container('Default', $this->manager);
        self::assertTrue($this->manager->started);
    }

    public function testInstantiatingContainerWithoutNameUsesDefaultAsName()
    {
        self::assertEquals('Default', $this->container->getName());
    }

    public function testPassingNameToConstructorInstantiatesContainerWithThatName()
    {
        $container = new Container('foo', $this->manager);
        self::assertEquals('foo', $container->getName());
    }

    public function testPassingNameStartingWithDigitToConstructorInstantiatesContainerWithThatName()
    {
        $container = new Container('0foo', $this->manager);
        self::assertEquals('0foo', $container->getName());
    }

    public function testUsingOldLaminas1NameIsStillValid()
    {
        $container = new Container('Laminas_Foo', $this->manager);
        self::assertEquals('Laminas_Foo', $container->getName());
    }

    public function testUsingNewLaminasNamespaceIsValid()
    {
        $container = new Container('Laminas\Foo', $this->manager);
        self::assertEquals('Laminas\Foo', $container->getName());
    }

    public function testPassingInvalidNameToConstructorRaisesException()
    {
        $tries = [
            'f!',
            'foo bar',
            '_foo',
            '__foo',
            '\foo',
            '\\foo'
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

    public function testContainerActsAsArray()
    {
        $this->container['foo'] = 'bar';
        self::assertTrue(isset($this->container['foo']));
        self::assertEquals('bar', $this->container['foo']);
        unset($this->container['foo']);
        self::assertFalse(isset($this->container['foo']));
    }

    public function testContainerActsAsObject()
    {
        $this->container->foo = 'bar';
        self::assertTrue(isset($this->container->foo));
        self::assertEquals('bar', $this->container->foo);
        unset($this->container->foo);
        self::assertFalse(isset($this->container->foo));
    }

    public function testDefaultManagerIsAlwaysPopulated()
    {
        $manager = Container::getDefaultManager();
        self::assertInstanceOf('Laminas\Session\ManagerInterface', $manager);
    }

    public function testCanSetDefaultManager()
    {
        $manager = new TestAsset\TestManager();
        Container::setDefaultManager($manager);
        self::assertSame($manager, Container::getDefaultManager());
    }

    public function testCanSetDefaultManagerToNull()
    {
        $manager = new TestAsset\TestManager();
        Container::setDefaultManager($manager);
        Container::setDefaultManager(null);
        self::assertNotSame($manager, Container::getDefaultManager());
    }

    public function testDefaultManagerUsedWhenNoManagerProvided()
    {
        $manager   = Container::getDefaultManager();
        $container = new Container();
        self::assertSame($manager, $container->getManager());
    }

    public function testContainerInstantiatesManagerWithDefaultsWhenNotInjected()
    {
        $container = new Container();
        $manager   = $container->getManager();
        self::assertInstanceOf('Laminas\Session\ManagerInterface', $manager);
        $config = $manager->getConfig();
        self::assertInstanceOf('Laminas\Session\Config\SessionConfig', $config);
        $storage = $manager->getStorage();
        self::assertInstanceOf('Laminas\Session\Storage\SessionArrayStorage', $storage);
    }

    public function testContainerAllowsInjectingManagerViaConstructor()
    {
        $config    = new StandardConfig([
            'storage' => 'Laminas\\Session\\Storage\\ArrayStorage',
        ]);
        $manager   = new TestAsset\TestManager($config);
        $container = new Container('Foo', $manager);
        self::assertSame($manager, $container->getManager());
    }

    public function testContainerWritesToStorage()
    {
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        self::assertTrue(isset($storage['Default']));
        self::assertTrue(isset($storage['Default']['foo']));
        self::assertEquals('bar', $storage['Default']['foo']);

        unset($this->container->foo);
        self::assertFalse(isset($storage['Default']['foo']));
    }

    public function testSettingExpirationSecondsUpdatesStorageMetadataForFullContainer()
    {
        $currentTimestamp = time();
        $this->container->setExpirationSeconds(3600);
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertArrayHasKey('EXPIRE', $metadata);
        self::assertEquals($currentTimestamp + 3600, $metadata['EXPIRE']);
    }

    public function testSettingExpirationSecondsForIndividualKeyUpdatesStorageMetadataForThatKey()
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

    public function testMultipleCallsToExpirationSecondsAggregates()
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

    public function testSettingExpirationSecondsUsesCurrentTime()
    {
        sleep(3);
        $this->container->setExpirationSeconds(2);
        $this->container->foo = 'bar';

        // Simulate a second request: overwrite the request time with current time()
        $_SERVER['REQUEST_TIME']       = time();
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);

        self::assertEquals('bar', $this->container->foo);
    }

    public function testPassingUnsetKeyToSetExpirationSecondsDoesNothing()
    {
        $this->container->setExpirationSeconds(3600, 'foo');
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testPassingUnsetKeyInArrayToSetExpirationSecondsDoesNothing()
    {
        $this->container->setExpirationSeconds(3600, ['foo']);
        $storage  = $this->manager->getStorage();
        $metadata = $storage->getMetadata($this->container->getName());
        self::assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testGetKeyWithContainerExpirationInPastResetsToNull()
    {
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        self::assertNull($this->container->foo);
    }

    public function testGetKeyWithExpirationInPastResetsToNull()
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE_KEYS' => ['foo' => $_SERVER['REQUEST_TIME'] - 18600]]);
        self::assertNull($this->container->foo);
        self::assertEquals('baz', $this->container->bar);
    }

    public function testKeyExistsWithContainerExpirationInPastReturnsFalse()
    {
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        self::assertFalse(isset($this->container->foo));
    }

    public function testKeyExistsWithExpirationInPastReturnsFalse()
    {
        $this->container->foo = 'bar';
        $this->container->bar = 'baz';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE_KEYS' => ['foo' => $_SERVER['REQUEST_TIME'] - 18600]]);
        self::assertFalse(isset($this->container->foo));
        self::assertTrue(isset($this->container->bar));
    }

    public function testKeyExistsWithContainerExpirationInPastWithSetExpirationSecondsReturnsFalse()
    {
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        $this->container->setExpirationSeconds(1);
        self::assertFalse(isset($this->container->foo));
    }

    public function testSettingExpiredKeyOverwritesExpiryMetadataForThatKey()
    {
        $this->container->foo = 'bar';
        $storage              = $this->manager->getStorage();
        $storage->setMetadata('Default', ['EXPIRE' => $_SERVER['REQUEST_TIME'] - 18600]);
        $this->container->foo = 'baz';
        self::assertTrue(isset($this->container->foo));
        self::assertEquals('baz', $this->container->foo);
        $metadata = $storage->getMetadata('Default');
        self::assertFalse(isset($metadata['EXPIRE_KEYS']['foo']));
    }

    public function testSettingExpirationHopsWithNoVariablesMarksContainerByWritingToStorage()
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

    public function testSettingExpirationHopsWithSingleKeyMarksContainerByWritingToStorage()
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

    public function testSettingExpirationHopsWithMultipleKeysMarksContainerByWritingToStorage()
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

    public function testContainerExpiresAfterSpecifiedHops()
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

    public function testInstantiatingMultipleContainersInSameRequestDoesNotCreateExtraHops()
    {
        $this->container->foo = 'bar';
        $this->container->setExpirationHops(1);

        $container = new Container('Default', $this->manager);
        self::assertEquals('bar', $container->foo);
        self::assertEquals('bar', $this->container->foo);
    }

    public function testKeyExpiresAfterSpecifiedHops()
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

    public function testInstantiatingMultipleContainersInSameRequestDoesNotCreateExtraKeyHops()
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

    public function testKeysExpireAfterSpecifiedHops()
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

    public function testCanIterateOverContainer()
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

    public function testIterationHonorsExpirationHops()
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

    public function testIterationHonorsExpirationTimestamps()
    {
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

    public function testValidationShouldNotRaiseErrorForMissingResponseObject()
    {
        $session       = new Container('test');
        $session->test = 42;
        self::assertEquals(42, $session->test);
    }

    public function testExchangeArray()
    {
        $this->container->offsetSet('old', 'old');
        self::assertTrue($this->container->offsetExists('old'));

        $old = $this->container->exchangeArray(['new' => 'new']);
        self::assertArrayHasKey('old', $old, "'exchangeArray' doesn't return an array of old items");
        self::assertFalse($this->container->offsetExists('old'), "'exchangeArray' doesn't remove old items");
        self::assertTrue($this->container->offsetExists('new'), "'exchangeArray' doesn't add the new array items");
    }

    public function testExchangeArrayObject()
    {
        $this->container->offsetSet('old', 'old');
        self::assertTrue($this->container->offsetExists('old'));

        $old = $this->container->exchangeArray(new \Laminas\Stdlib\ArrayObject(['new' => 'new']));
        self::assertArrayHasKey('old', $old, "'exchangeArray' doesn't return an array of old items");
        self::assertFalse($this->container->offsetExists('old'), "'exchangeArray' doesn't remove old items");
        self::assertTrue($this->container->offsetExists('new'), "'exchangeArray' doesn't add the new array items");
    }

    public function testMultiDimensionalUnset()
    {
        $this->container->foo = ['bar' => 'baz'];
        unset($this->container['foo']['bar']);
        self::assertSame([], $this->container->foo);
    }

    public function testUpgradeBehaviors()
    {
        $storage        = $this->manager->getStorage();
        $storage['foo'] = new ArrayObject(['bar' => 'baz']);

        $container = new Container('foo', $this->manager);
        self::assertEquals('baz', $container->bar);
        $container->baz = 'boo';
        self::assertEquals('boo', $storage['foo']['baz']);
    }

    public function testGetArrayCopyAfterExchangeArray()
    {
        $this->container->exchangeArray(['foo' => 'bar']);

        $contents = $this->container->getArrayCopy();

        self::assertIsArray($contents);
        self::assertArrayHasKey('foo', $contents, "'getArrayCopy' doesn't return exchanged array");
    }
}
