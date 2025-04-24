<?php

declare(strict_types=1);

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\SaveHandler\Cache;
use Laminas\Session\SessionManager;
use LaminasTest\Session\TestAsset\TestSimpleCacheAdapter;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function hash;
use function serialize;
use function unserialize;

class SimpleCacheTest extends TestCase
{
    protected Cache $cacheAdapter;
    protected ReflectionClass $cacheAdapterReflection;

    protected function setUp(): void
    {
        $this->cacheAdapter           = new Cache(new TestSimpleCacheAdapter());
        $this->cacheAdapterReflection = new ReflectionClass($this->cacheAdapter);
    }

    /**
     * @throws ReflectionException
     */
    public function testOperationsWithCacheAdapter(): void
    {
        self::assertTrue($this->cacheAdapter->write('testKey1', 'testVal1'));
        $result = $this->cacheAdapter->read('testKey1');
        self::assertEquals('testVal1', $result);

        self::assertTrue($this->cacheAdapter->write('testKey2', serialize(['valueKey' => 2])));

        $result2 = $this->cacheAdapter->read('testKey2');
        self::assertIsString($result2);
        self::assertEquals(['valueKey' => 2], unserialize($result2));

        $cacheStorage = $this->cacheAdapterReflection->getProperty('cacheStorage')->getValue($this->cacheAdapter);

        self::assertInstanceOf(TestSimpleCacheAdapter::class, $cacheStorage);

        $storageReflection = (new ReflectionClass($cacheStorage))->getProperty('storage')->getValue($cacheStorage);

        self::assertIsArray($storageReflection);
        self::assertCount(2, $storageReflection);

        $this->cacheAdapter->destroy('testKey1');
        $cacheStorage = $this->cacheAdapterReflection->getProperty('cacheStorage')->getValue($this->cacheAdapter);
        self::assertInstanceOf(TestSimpleCacheAdapter::class, $cacheStorage);
        $storageReflection = (new ReflectionClass($cacheStorage))->getProperty('storage')->getValue($cacheStorage);
        self::assertIsArray($storageReflection);

        $hash = (new ReflectionMethod($this->cacheAdapter, 'getCacheKey'))->invoke($this->cacheAdapter, 'testKey2');
        $this->assertSame(hash('xxh32', 'testKey2'), $hash);

        self::assertArrayHasKey($hash, $storageReflection);
        self::assertCount(1, $storageReflection);
    }

    public function testContainerWithCacheSaveHandler(): void
    {
        $config  = (new StandardConfig())->setName('TestConfigName');
        $manager = (new SessionManager($config))->setSaveHandler($this->cacheAdapter);

        $cacheSaveHandler = $manager->getSaveHandler();

        self::assertSame($cacheSaveHandler, $this->cacheAdapter);

        $container = new Container('TestContainerName');
        $container::setDefaultManager($manager);

        $container->testKey = 'testValue';

        self::assertSame('testValue', $container->offsetGet('testKey'));

        $container->offsetUnset('testKey');
        self::assertFalse($container->offsetExists('testKey'));
    }
}
