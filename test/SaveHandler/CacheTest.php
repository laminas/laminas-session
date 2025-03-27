<?php

declare(strict_types=1);

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\SaveHandler\Cache;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;

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
    protected array $testArray;

    /**
     * Array to collect used Cache objects, so they are not
     * destroyed before all tests are done and session is not closed
     */
    protected array $usedSaveHandlers = [];

    protected function setUp(): void
    {
        $this->testArray = ['foo' => 'bar', 'bar' => ['foo' => 'bar']];
    }

    public function testReadWrite(): void
    {
        $cacheStorage = $this->createMock(CacheInterface::class);
        $cacheStorage->expects(self::any())
            ->method('set')
            ->with('242', self::anything())
            ->willReturnCallback(static function (string $firstArgs, string $secondArgs) use ($cacheStorage): bool {
                $cacheStorage->expects(self::any())
                ->method('get')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });

        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage, '', '');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $data = $saveHandler->read($id);

        self::assertIsString($data);

        $data = unserialize($data);
        self::assertEquals(
            $this->testArray,
            $data,
            'Expected ' . var_export($this->testArray, true) . "\nbut got: " . var_export($data, true)
        );
    }

    public function testReadWriteComplex(): void
    {
        $cacheStorage = $this->createMock(CacheInterface::class);
        $cacheStorage->expects(self::any())
            ->method('set')
            ->with('242', self::anything())
            ->willReturnCallback(static function (string $firstArgs, string $secondArgs) use ($cacheStorage): bool {
                $cacheStorage->expects(self::any())
                ->method('get')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage, '', '');
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $result = $saveHandler->read($id);
        self::assertIsString($result);
        self::assertEquals($this->testArray, unserialize($result));
    }

    public function testReadWriteTwice(): void
    {
        $cacheStorage = $this->createMock(CacheInterface::class);
        $cacheStorage->expects(self::exactly(2))
            ->method('set')
            ->with('242', self::anything())
            ->willReturnCallback(static function (string $firstArgs, string $secondArgs) use ($cacheStorage): bool {
                $cacheStorage->expects(self::any())
                ->method('get')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });

        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage, '', '');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $first = $saveHandler->read($id);
        self::assertIsString($first);
        self::assertEquals($this->testArray, unserialize($first));

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $second = $saveHandler->read($id);
        self::assertIsString($second);
        self::assertEquals($this->testArray, unserialize($second));
    }

    public function testReadWillReturnFalseOnCacheMiss(): void
    {
        $cacheStorage = $this->createMock(CacheInterface::class);
        $cacheStorage->expects(self::any())->method('get')->willReturn(false);
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage, '', '');

        $id = '242';

        $data = $saveHandler->read($id);

        self::assertFalse($data);
    }

    public function testDestroyReturnsTrueEvenWhenSessionDoesNotExist(): void
    {
        $cacheStorage             = $this->createMock(CacheInterface::class);
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage, '', '');

        $id = '242';

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }

    public function testDestroyReturnsTrueWhenSessionIsDeleted(): void
    {
        $cacheStorage = $this->createMock(CacheInterface::class);
        $cacheStorage->expects(self::any())
            ->method('set')
            ->with('242', self::anything())
            ->willReturnCallback(static function (string $firstArgs, string $secondArgs) use ($cacheStorage): bool {
                $cacheStorage->expects(self::any())
                ->method('get')
                ->with('242')
                ->willReturn($secondArgs);
                return true;
            });

        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage, '', '');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }
}
