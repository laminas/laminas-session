<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use Laminas\Session\Exception\RuntimeException;
use Laminas\Session\Storage\ArrayStorage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Storage\ArrayStorage
 */
final class StorageTest extends TestCase
{
    private ArrayStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
    }

    public function testStorageAllowsArrayAccess(): void
    {
        $this->storage['foo'] = 'bar';
        self::assertTrue(isset($this->storage['foo']));
        self::assertEquals('bar', $this->storage['foo']);
        unset($this->storage['foo']);
        self::assertFalse(isset($this->storage['foo']));
    }

    public function testStorageAllowsPropertyAccess(): void
    {
        $this->storage->foo = 'bar';
        self::assertTrue(isset($this->storage->foo));
        self::assertEquals('bar', $this->storage->foo);
        unset($this->storage->foo);
        self::assertFalse(isset($this->storage->foo));
    }

    public function testStorageAllowsSettingMetadata(): void
    {
        $this->storage->setMetadata('TEST', 'foo');
        self::assertEquals('foo', $this->storage->getMetadata('TEST'));
    }

    public function testSettingArrayMetadataMergesOnSubsequentRequests(): void
    {
        $this->storage->setMetadata('TEST', ['foo' => 'bar', 'bar' => 'baz']);
        $this->storage->setMetadata('TEST', ['foo' => 'baz', 'baz' => 'bat', 'lonesome']);
        $expected = ['foo' => 'baz', 'bar' => 'baz', 'baz' => 'bat', 'lonesome'];
        self::assertEquals($expected, $this->storage->getMetadata('TEST'));
    }

    public function testSettingArrayMetadataWithOverwriteFlagOverwritesExistingData(): void
    {
        $this->storage->setMetadata('TEST', ['foo' => 'bar', 'bar' => 'baz']);
        $this->storage->setMetadata('TEST', ['foo' => 'baz', 'baz' => 'bat', 'lonesome'], true);
        $expected = ['foo' => 'baz', 'baz' => 'bat', 'lonesome'];
        self::assertEquals($expected, $this->storage->getMetadata('TEST'));
    }

    public function testLockWithNoKeyMakesStorageReadOnly(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->lock();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot set key "foo" due to locking');
        $this->storage->foo = 'baz';
    }

    public function testLockWithNoKeyMarksEntireStorageLocked(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock();
        self::assertTrue($this->storage->isLocked());
        self::assertTrue($this->storage->isLocked('foo'));
        self::assertTrue($this->storage->isLocked('bar'));
    }

    public function testLockWithKeyMakesOnlyThatKeyReadOnly(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');

        $this->storage->bar = 'baz';
        self::assertEquals('baz', $this->storage->bar);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot set key "foo" due to locking');
        $this->storage->foo = 'baz';
    }

    public function testLockWithKeyMarksOnlyThatKeyLocked(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock('foo');
        self::assertTrue($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->isLocked('bar'));
    }

    public function testLockWithNoKeyShouldWriteToMetadata(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->lock();
        $locked = $this->storage->getMetadata('_READONLY');
        self::assertTrue($locked);
    }

    public function testLockWithKeyShouldWriteToMetadata(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');
        $locks = $this->storage->getMetadata('_LOCKS');
        self::assertIsArray($locks);
        self::assertArrayHasKey('foo', $locks);
    }

    public function testUnlockShouldUnlockEntireObject(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock();
        $this->storage->unlock();
        self::assertFalse($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->isLocked('bar'));
    }

    public function testUnlockShouldUnlockSelectivelyLockedKeys(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock('foo');
        $this->storage->unlock();
        self::assertFalse($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->isLocked('bar'));
    }

    public function testUnlockWithKeyShouldUnlockOnlyThatKey(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock();
        $this->storage->unlock('foo');
        self::assertFalse($this->storage->isLocked('foo'));
        self::assertTrue($this->storage->isLocked('bar'));
    }

    public function testUnlockWithKeyOfSelectiveLockShouldUnlockThatKey(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');
        $this->storage->unlock('foo');
        self::assertFalse($this->storage->isLocked('foo'));
    }

    public function testClearWithNoArgumentsRemovesExistingData(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';

        $this->storage->clear();
        $data = $this->storage->toArray();
        self::assertSame([], $data);
    }

    public function testClearWithNoArgumentsRemovesExistingMetadata(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');
        $this->storage->setMetadata('FOO', 'bar');
        $this->storage->clear();

        self::assertFalse($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->getMetadata('FOO'));
    }

    public function testClearWithArgumentRemovesExistingDataForThatKeyOnly(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';

        $this->storage->clear('foo');
        $data = $this->storage->toArray();
        self::assertSame(['bar' => 'baz'], $data);
    }

    public function testClearWithArgumentRemovesExistingMetadataForThatKeyOnly(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock('foo');
        $this->storage->lock('bar');
        $this->storage->setMetadata('foo', 'bar');
        $this->storage->setMetadata('bar', 'baz');
        $this->storage->clear('foo');

        self::assertFalse($this->storage->isLocked('foo'));
        self::assertTrue($this->storage->isLocked('bar'));
        self::assertFalse($this->storage->getMetadata('foo'));
        self::assertEquals('baz', $this->storage->getMetadata('bar'));
    }

    public function testClearWhenStorageMarkedImmutableRaisesException(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->markImmutable();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot clear storage as it is marked immutable');
        $this->storage->clear();
    }

    public function testRequestAccessTimeIsPreservedEvenInFactoryMethod(): void
    {
        self::assertNotEmpty($this->storage->getRequestAccessTime());
        $this->storage->fromArray([]);
        self::assertNotEmpty($this->storage->getRequestAccessTime());
    }

    public function testToArrayWithMetaData(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->setMetadata('foo', 'bar');
        $expected = [
            '__Laminas' => [
                '_REQUEST_ACCESS_TIME' => $this->storage->getRequestAccessTime(),
                'foo'                  => 'bar',
            ],
            'foo'       => 'bar',
            'bar'       => 'baz',
        ];
        self::assertSame($expected, $this->storage->toArray(true));
    }

    public function testUnsetMultidimensional(): void
    {
        $this->storage['foo'] = ['bar' => ['baz' => 'boo']];
        unset($this->storage['foo']['bar']['baz']);
        unset($this->storage['foo']['bar']);

        self::assertFalse(isset($this->storage['foo']['bar']));
    }
}
