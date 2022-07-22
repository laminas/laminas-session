<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\SessionArrayStorage;
use PHPUnit\Framework\TestCase;

use function var_export;

/**
 * @covers \Laminas\Session\Storage\SessionArrayStorage
 */
class SessionArrayStorageTest extends TestCase
{
    private \Laminas\Session\Storage\SessionArrayStorage $storage;

    protected function setUp(): void
    {
        $_SESSION      = [];
        $this->storage = new SessionArrayStorage();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testStorageWritesToSessionSuperglobal(): void
    {
        $this->storage['foo'] = 'bar';
        self::assertSame($_SESSION['foo'], $this->storage->foo);
        unset($this->storage['foo']);
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    public function testPassingArrayToConstructorOverwritesSessionSuperglobal(): void
    {
        $_SESSION['foo'] = 'bar';
        $array           = ['foo' => 'FOO'];
        $storage         = new SessionArrayStorage($array);
        $expected        = [
            'foo'       => 'FOO',
            '__Laminas' => [
                '_REQUEST_ACCESS_TIME' => $storage->getRequestAccessTime(),
            ],
        ];
        self::assertSame($expected, $_SESSION);
    }

    public function testModifyingSessionSuperglobalDirectlyUpdatesStorage(): void
    {
        $_SESSION['foo'] = 'bar';
        self::assertTrue(isset($this->storage['foo']));
    }

    public function testDestructorSetsSessionToArray(): void
    {
        $this->storage->foo = 'bar';
        $expected           = [
            '__Laminas' => [
                '_REQUEST_ACCESS_TIME' => $this->storage->getRequestAccessTime(),
            ],
            'foo'       => 'bar',
        ];
        $this->storage->__destruct();
        self::assertSame($expected, $_SESSION);
    }

    public function testModifyingOneSessionObjectModifiesTheOther(): void
    {
        $this->storage->foo = 'bar';
        $storage            = new SessionArrayStorage();
        $storage->bar       = 'foo';
        self::assertEquals('foo', $this->storage->bar);
    }

    public function testMarkingOneSessionObjectImmutableShouldMarkOtherInstancesImmutable(): void
    {
        $this->storage->foo = 'bar';
        $storage            = new SessionArrayStorage();
        self::assertEquals('bar', $storage['foo']);
        $this->storage->markImmutable();
        self::assertTrue($storage->isImmutable(), var_export($_SESSION, true));
    }

    public function testAssignment(): void
    {
        $_SESSION['foo'] = 'bar';
        self::assertEquals('bar', $this->storage['foo']);
    }

    public function testMultiDimensionalAssignment(): void
    {
        $_SESSION['foo']['bar'] = 'baz';
        self::assertEquals('baz', $this->storage['foo']['bar']);
    }

    public function testUnset(): void
    {
        $_SESSION['foo'] = 'bar';
        unset($_SESSION['foo']);
        self::assertFalse(isset($this->storage['foo']));
    }

    public function testMultiDimensionalUnset(): void
    {
        $this->storage['foo'] = ['bar' => ['baz' => 'boo']];
        unset($this->storage['foo']['bar']['baz']);
        self::assertFalse(isset($this->storage['foo']['bar']['baz']));
        unset($this->storage['foo']['bar']);
        self::assertFalse(isset($this->storage['foo']['bar']));
    }

    public function testSessionWorksWithContainer(): void
    {
        // Run without any validators; session ID is often invalid in CLI
        $container      = new Container(
            'test',
            new SessionManager(null, null, null, [], ['attach_default_validators' => false])
        );
        $container->foo = 'bar';

        self::assertSame($container->foo, $_SESSION['test']['foo']);
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

    public function testUndefinedSessionManipulation(): void
    {
        $this->storage['foo']        = 'bar';
        $this->storage['bar'][]      = 'bar';
        $this->storage['baz']['foo'] = 'bar';

        $expected = [
            '__Laminas' => [
                '_REQUEST_ACCESS_TIME' => $this->storage->getRequestAccessTime(),
            ],
            'foo'       => 'bar',
            'bar'       => ['bar'],
            'baz'       => ['foo' => 'bar'],
        ];
        self::assertSame($expected, $this->storage->toArray(true));
    }

    /**
     * @runInSeparateProcess
     */
    public function testExpirationHops(): void
    {
        // since we cannot explicitly test reinitializing the session
        // we will act in how session manager would in this case.
        $storage = new SessionArrayStorage();
        $manager = new SessionManager(null, $storage);
        $manager->start();

        $container      = new Container('test');
        $container->foo = 'bar';
        $container->setExpirationHops(1);

        $copy     = $_SESSION;
        $_SESSION = null;
        $storage->init($copy);
        self::assertEquals('bar', $container->foo);

        $copy     = $_SESSION;
        $_SESSION = null;
        $storage->init($copy);
        self::assertNull($container->foo);
    }

    /**
     * @runInSeparateProcess
     */
    public function testPreserveRequestAccessTimeAfterStart(): void
    {
        $manager = new SessionManager(null, $this->storage);
        self::assertGreaterThan(0, $this->storage->getRequestAccessTime());
        $manager->start();
        self::assertGreaterThan(0, $this->storage->getRequestAccessTime());
    }

    public function testGetArrayCopyFromContainer(): void
    {
        $container      = new Container('test');
        $container->foo = 'bar';
        $container->baz = 'qux';
        self::assertSame(['foo' => 'bar', 'baz' => 'qux'], $container->getArrayCopy());
    }

    public function testClearMetaDataIfDontExistInSession(): void
    {
        $this->storage->setMetadata('foo', 'bar');
        $this->storage->clear('foo');

        self::assertFalse($this->storage->getMetaData('foo'));
    }

    public function testClearRemoveFromSession(): void
    {
        $this->storage->foo = 'bar';
        $this->storage->clear('foo');

        self::assertArrayNotHasKey('foo', $_SESSION);
    }
}
