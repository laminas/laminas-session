<?php

declare(strict_types=1);

namespace LaminasTest\Session\SaveHandler;

use Laminas\Cache\Storage\StorageInterface;
use Laminas\Session\SaveHandler\Cache;
use PHPUnit\Framework\TestCase;

use function is_string;
use function serialize;
use function unserialize;
use function var_export;

/**
 * Unit testing for DbTable include all tests for
 * regular session handling
 *
 * @covers \Laminas\Session\SaveHandler\Cache
 */
class CacheTest extends TestCase
{
    /** @var CacheAdapter */
    protected $cache;

    /** @var array */
    protected $testArray;

    /**
     * Array to collect used Cache objects, so they are not
     * destroyed before all tests are done and session is not closed
     *
     * @var array
     */
    protected $usedSaveHandlers = [];

    protected function setUp(): void
    {
        $this->testArray = ['foo' => 'bar', 'bar' => ['foo' => 'bar']];
    }

    public function testReadWrite(): void
    {
        $cacheStorage = $this->createMock(StorageInterface::class);
        $cacheStorage->expects(self::any())
            ->method('setItem')
            ->with('242', self::anything())
            ->willReturnCallback(function (string $firstArgs, string $secondArgs) use ($cacheStorage) {
                $cacheStorage->expects(self::any())
                ->method('getItem')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });

        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage);

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $data = unserialize($saveHandler->read($id));
        self::assertEquals(
            $this->testArray,
            $data,
            'Expected ' . var_export($this->testArray, true) . "\nbut got: " . var_export($data, true)
        );
    }

    public function testReadWriteComplex(): void
    {
        $cacheStorage = $this->createMock(StorageInterface::class);
        $cacheStorage->expects(self::any())
            ->method('setItem')
            ->with('242', self::anything())
            ->willReturnCallback(function (string $firstArgs, string $secondArgs) use ($cacheStorage) {
                $cacheStorage->expects(self::any())
                ->method('getItem')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    public function testReadWriteTwice(): void
    {
        $cacheStorage = $this->createMock(StorageInterface::class);
        $cacheStorage->expects(self::exactly(2))
            ->method('setItem')
            ->with('242', self::anything())
            ->willReturnCallback(function (string $firstArgs, string $secondArgs) use ($cacheStorage) {
                $cacheStorage->expects(self::any())
                ->method('getItem')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });

        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage);

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    public function testReadShouldAlwaysReturnString(): void
    {
        $cacheStorage = $this->createMock(StorageInterface::class);
        $cacheStorage->expects(self::any())->method('getItem')->willReturn(null);
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage);

        $id = '242';

        $data = $saveHandler->read($id);

        self::assertTrue(is_string($data));
    }

    public function testDestroyReturnsTrueEvenWhenSessionDoesNotExist(): void
    {
        $cacheStorage             = $this->createMock(StorageInterface::class);
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage);

        $id = '242';

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }

    public function testDestroyReturnsTrueWhenSessionIsDeleted(): void
    {
        $cacheStorage = $this->createMock(StorageInterface::class);
        $cacheStorage->expects(self::any())
            ->method('setItem')
            ->with('242', self::anything())
            ->willReturnCallback(function (string $firstArgs, string $secondArgs) use ($cacheStorage) {
                $cacheStorage->expects(self::any())
                ->method('getItem')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });

        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage);

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }
}
