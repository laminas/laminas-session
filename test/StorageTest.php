<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session;

use Laminas\Session\Storage\ArrayStorage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Storage\ArrayStorage
 */
class StorageTest extends TestCase
{
    /**
     * @var ArrayStorage
     */
    protected $storage;

    protected function setUp(): void
    {
        $this->storage = new ArrayStorage();
    }

    public function testStorageAllowsArrayAccess()
    {
        $this->storage['foo'] = 'bar';
        self::assertTrue(isset($this->storage['foo']));
        self::assertEquals('bar', $this->storage['foo']);
        unset($this->storage['foo']);
        self::assertFalse(isset($this->storage['foo']));
    }

    public function testStorageAllowsPropertyAccess()
    {
        $this->storage->foo = 'bar';
        self::assertTrue(isset($this->storage->foo));
        self::assertEquals('bar', $this->storage->foo);
        unset($this->storage->foo);
        self::assertFalse(isset($this->storage->foo));
    }

    public function testStorageAllowsSettingMetadata()
    {
        $this->storage->setMetadata('TEST', 'foo');
        self::assertEquals('foo', $this->storage->getMetadata('TEST'));
    }

    public function testSettingArrayMetadataMergesOnSubsequentRequests()
    {
        $this->storage->setMetadata('TEST', ['foo' => 'bar', 'bar' => 'baz']);
        $this->storage->setMetadata('TEST', ['foo' => 'baz', 'baz' => 'bat', 'lonesome']);
        $expected = ['foo' => 'baz', 'bar' => 'baz', 'baz' => 'bat', 'lonesome'];
        self::assertEquals($expected, $this->storage->getMetadata('TEST'));
    }

    public function testSettingArrayMetadataWithOverwriteFlagOverwritesExistingData()
    {
        $this->storage->setMetadata('TEST', ['foo' => 'bar', 'bar' => 'baz']);
        $this->storage->setMetadata('TEST', ['foo' => 'baz', 'baz' => 'bat', 'lonesome'], true);
        $expected = ['foo' => 'baz', 'baz' => 'bat', 'lonesome'];
        self::assertEquals($expected, $this->storage->getMetadata('TEST'));
    }

    public function testLockWithNoKeyMakesStorageReadOnly()
    {
        $this->storage->foo = 'bar';
        $this->storage->lock();
        $this->expectException('Laminas\Session\Exception\RuntimeException');
        $this->expectExceptionMessage('Cannot set key "foo" due to locking');
        $this->storage->foo = 'baz';
    }

    public function testLockWithNoKeyMarksEntireStorageLocked()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock();
        self::assertTrue($this->storage->isLocked());
        self::assertTrue($this->storage->isLocked('foo'));
        self::assertTrue($this->storage->isLocked('bar'));
    }

    public function testLockWithKeyMakesOnlyThatKeyReadOnly()
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');

        $this->storage->bar = 'baz';
        self::assertEquals('baz', $this->storage->bar);

        $this->expectException('Laminas\Session\Exception\RuntimeException');
        $this->expectExceptionMessage('Cannot set key "foo" due to locking');
        $this->storage->foo = 'baz';
    }

    public function testLockWithKeyMarksOnlyThatKeyLocked()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock('foo');
        self::assertTrue($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->isLocked('bar'));
    }

    public function testLockWithNoKeyShouldWriteToMetadata()
    {
        $this->storage->foo = 'bar';
        $this->storage->lock();
        $locked = $this->storage->getMetadata('_READONLY');
        self::assertTrue($locked);
    }

    public function testLockWithKeyShouldWriteToMetadata()
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');
        $locks = $this->storage->getMetadata('_LOCKS');
        self::assertIsArray($locks);
        self::assertArrayHasKey('foo', $locks);
    }

    public function testUnlockShouldUnlockEntireObject()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock();
        $this->storage->unlock();
        self::assertFalse($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->isLocked('bar'));
    }

    public function testUnlockShouldUnlockSelectivelyLockedKeys()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock('foo');
        $this->storage->unlock();
        self::assertFalse($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->isLocked('bar'));
    }

    public function testUnlockWithKeyShouldUnlockOnlyThatKey()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->lock();
        $this->storage->unlock('foo');
        self::assertFalse($this->storage->isLocked('foo'));
        self::assertTrue($this->storage->isLocked('bar'));
    }

    public function testUnlockWithKeyOfSelectiveLockShouldUnlockThatKey()
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');
        $this->storage->unlock('foo');
        self::assertFalse($this->storage->isLocked('foo'));
    }

    public function testClearWithNoArgumentsRemovesExistingData()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';

        $this->storage->clear();
        $data = $this->storage->toArray();
        self::assertSame([], $data);
    }

    public function testClearWithNoArgumentsRemovesExistingMetadata()
    {
        $this->storage->foo = 'bar';
        $this->storage->lock('foo');
        $this->storage->setMetadata('FOO', 'bar');
        $this->storage->clear();

        self::assertFalse($this->storage->isLocked('foo'));
        self::assertFalse($this->storage->getMetadata('FOO'));
    }

    public function testClearWithArgumentRemovesExistingDataForThatKeyOnly()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';

        $this->storage->clear('foo');
        $data = $this->storage->toArray();
        self::assertSame(['bar' => 'baz'], $data);
    }

    public function testClearWithArgumentRemovesExistingMetadataForThatKeyOnly()
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

    public function testClearWhenStorageMarkedImmutableRaisesException()
    {
        $this->storage->foo = 'bar';
        $this->storage->bar = 'baz';
        $this->storage->markImmutable();
        $this->expectException('Laminas\Session\Exception\RuntimeException');
        $this->expectExceptionMessage('Cannot clear storage as it is marked immutable');
        $this->storage->clear();
    }

    public function testRequestAccessTimeIsPreservedEvenInFactoryMethod()
    {
        self::assertNotEmpty($this->storage->getRequestAccessTime());
        $this->storage->fromArray([]);
        self::assertNotEmpty($this->storage->getRequestAccessTime());
    }

    public function testToArrayWithMetaData()
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

    public function testUnsetMultidimensional()
    {
        $this->storage['foo'] = ['bar' => ['baz' => 'boo']];
        unset($this->storage['foo']['bar']['baz']);
        unset($this->storage['foo']['bar']);

        self::assertFalse(isset($this->storage['foo']['bar']));
    }
}
