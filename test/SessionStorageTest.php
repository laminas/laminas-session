<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session;

use Laminas\Session\Storage\SessionStorage;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\Storage\SessionStorage
 */
class SessionStorageTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION      = [];
        $this->storage = new SessionStorage();
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testSessionStorageInheritsFromArrayStorage()
    {
        self::assertInstanceOf('Laminas\Session\Storage\SessionStorage', $this->storage);
        self::assertInstanceOf('Laminas\Session\Storage\ArrayStorage', $this->storage);
    }

    public function testStorageWritesToSessionSuperglobal()
    {
        $this->storage['foo'] = 'bar';
        self::assertSame($_SESSION, $this->storage);
        unset($this->storage['foo']);
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    public function testPassingArrayToConstructorOverwritesSessionSuperglobal()
    {
        $_SESSION['foo'] = 'bar';
        $array           = ['foo' => 'FOO'];
        $storage         = new SessionStorage($array);
        $expected        = [
            'foo'       => 'FOO',
            '__Laminas' => [
                '_REQUEST_ACCESS_TIME' => $storage->getRequestAccessTime(),
            ],
        ];
        self::assertSame($expected, $_SESSION->getArrayCopy());
    }

    public function testModifyingSessionSuperglobalDirectlyUpdatesStorage()
    {
        $_SESSION['foo'] = 'bar';
        self::assertTrue(isset($this->storage['foo']));
    }

    public function testDestructorSetsSessionToArray()
    {
        $this->storage->foo = 'bar';
        $expected = [
            '__Laminas' => [
                '_REQUEST_ACCESS_TIME' => $this->storage->getRequestAccessTime(),
            ],
            'foo' => 'bar',
        ];
        $this->storage->__destruct();
        self::assertSame($expected, $_SESSION);
    }

    public function testModifyingOneSessionObjectModifiesTheOther()
    {
        $this->storage->foo = 'bar';
        $storage            = new SessionStorage();
        $storage->bar       = 'foo';
        self::assertEquals('foo', $this->storage->bar);
    }

    public function testMarkingOneSessionObjectImmutableShouldMarkOtherInstancesImmutable()
    {
        $this->storage->foo = 'bar';
        $storage            = new SessionStorage();
        self::assertEquals('bar', $storage['foo']);
        $this->storage->markImmutable();
        self::assertTrue($storage->isImmutable(), var_export($_SESSION, 1));
    }

    public function testMultiDimensionalUnset()
    {
        $this->storage['foo'] = ['bar' => ['baz' => 'boo']];
        unset($this->storage['foo']['bar']['baz']);
        self::assertFalse(isset($this->storage['foo']['bar']['baz']));
        unset($this->storage['foo']['bar']);
        self::assertFalse(isset($this->storage['foo']['bar']));
    }
}
