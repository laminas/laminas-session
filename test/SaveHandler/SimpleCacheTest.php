<?php

declare(strict_types=1);

namespace LaminasTest\Session\SaveHandler;

use InvalidArgumentException;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\Exception\SimpleCacheInvalidArgumentException;
use Laminas\Session\SaveHandler\Cache;
use Laminas\Session\SessionManager;
use LaminasTest\Session\TestAsset\TestSimpleCacheAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

use function hash;
use function preg_match;
use function preg_quote;
use function serialize;
use function sprintf;
use function unserialize;

class SimpleCacheTest extends TestCase
{
    protected Cache $cacheAdapter;
    protected ReflectionClass $cacheAdapterReflection;
    protected TestSimpleCacheAdapter&MockObject $adapterMock;
    protected PsrInvalidArgumentException $psrException;

    protected function setUp(): void
    {
        $this->cacheAdapter           = new Cache(new TestSimpleCacheAdapter());
        $this->cacheAdapterReflection = new ReflectionClass($this->cacheAdapter);

        $this->adapterMock  = $this->createMock(TestSimpleCacheAdapter::class);
        $this->psrException = new class extends InvalidArgumentException implements PsrInvalidArgumentException
        {
        };
    }

    /**
     * @throws ReflectionException
     */
    public function testOperationsWithCacheAdapter(): void
    {
        self::assertTrue($this->cacheAdapter->write('testKey1', 'testVal1'));
        $result = $this->cacheAdapter->read('testKey1');
        self::assertEquals('testVal1', $result);

        // Because keys are hashed, characters invalid for Psr\SimpleCache\CacheInterface are allowed here
        $invalidCharactersKey = '\invalid@test{key}';
        self::assertTrue($this->cacheAdapter->write($invalidCharactersKey, serialize(['valueKey' => 2])));

        $result2 = $this->cacheAdapter->read($invalidCharactersKey);
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

        $hash  = (new ReflectionMethod($this->cacheAdapter, 'getCacheKey'))
            ->invoke($this->cacheAdapter, $invalidCharactersKey);
        $regex = sprintf('/[%s]/', preg_quote(TestSimpleCacheAdapter::INVALID_KEY_CHARS, '/'));

        self::assertIsString($hash);
        self::assertEquals(0, preg_match($regex, $hash));
        self::assertSame(hash('xxh32', $invalidCharactersKey), $hash);

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

    public function testWriteThrowsOwnException(): void
    {
        $testKey   = 'testKey';
        $testValue = 'testValue';
        $hash      = hash('xxh32', $testKey);

        $this->adapterMock->expects(self::once())
            ->method('set')
            ->with($hash, $testValue)
            ->willThrowException(new $this->psrException('Write Exception', 1));

        $saveHandler = new Cache($this->adapterMock);

        self::expectException(SimpleCacheInvalidArgumentException::class);
        self::expectExceptionCode(1);
        self::expectExceptionMessage('Write Exception');
        $saveHandler->write($testKey, $testValue);
    }

    public function testReadThrowsOwnException(): void
    {
        $testKey = 'testKey';
        $hash    = hash('xxh32', $testKey);

        $this->adapterMock->expects(self::once())
            ->method('has')
            ->with($hash)
            ->willReturn(true);

        $this->adapterMock->expects(self::once())
            ->method('get')
            ->with($hash)
            ->willThrowException(new $this->psrException('Read Exception', 2));

        $saveHandler = new Cache($this->adapterMock);

        self::expectException(SimpleCacheInvalidArgumentException::class);
        self::expectExceptionCode(2);
        self::expectExceptionMessage('Read Exception');
        $saveHandler->read($testKey);
    }

    public function testDestroyThrowsOwnException(): void
    {
        $testKey = 'testKey';
        $hash    = hash('xxh32', $testKey);

        $this->adapterMock->expects(self::once())
            ->method('has')
            ->with($hash)
            ->willReturn(true);

        $this->adapterMock->expects(self::once())
            ->method('delete')
            ->with($hash)
            ->willThrowException(new $this->psrException('Delete Exception', 3));

        $saveHandler = new Cache($this->adapterMock);

        self::expectException(SimpleCacheInvalidArgumentException::class);
        self::expectExceptionCode(3);
        self::expectExceptionMessage('Delete Exception');
        $saveHandler->destroy($testKey);
    }
}
