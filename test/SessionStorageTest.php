<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session;

use PHPUnit\Framework\TestCase;
use Zend\Session\Storage\SessionStorage;

/**
 * @covers \Zend\Session\Storage\SessionStorage
 */
class SessionStorageTest extends TestCase
{
    protected function setUp()
    {
        $_SESSION = [];
        $this->storage = new SessionStorage;
    }

    protected function tearDown()
    {
        $_SESSION = [];
    }

    public function testSessionStorageInheritsFromArrayStorage()
    {
        $this->assertInstanceOf('Zend\Session\Storage\SessionStorage', $this->storage);
        $this->assertInstanceOf('Zend\Session\Storage\ArrayStorage', $this->storage);
    }

    public function testStorageWritesToSessionSuperglobal()
    {
        $this->storage['foo'] = 'bar';
        $this->assertSame($_SESSION, $this->storage);
        unset($this->storage['foo']);
        $this->assertArrayNotHasKey('foo', $_SESSION);
    }

    public function testPassingArrayToConstructorOverwritesSessionSuperglobal()
    {
        $_SESSION['foo'] = 'bar';
        $array   = ['foo' => 'FOO'];
        $storage = new SessionStorage($array);
        $expected = [
            'foo' => 'FOO',
            '__ZF' => [
                '_REQUEST_ACCESS_TIME' => $storage->getRequestAccessTime(),
            ],
        ];
        $this->assertSame($expected, $_SESSION->getArrayCopy());
    }

    public function testModifyingSessionSuperglobalDirectlyUpdatesStorage()
    {
        $_SESSION['foo'] = 'bar';
        $this->assertTrue(isset($this->storage['foo']));
    }

    public function testDestructorSetsSessionToArray()
    {
        $this->storage->foo = 'bar';
        $expected = [
            '__ZF' => [
                '_REQUEST_ACCESS_TIME' => $this->storage->getRequestAccessTime(),
            ],
            'foo' => 'bar',
        ];
        $this->storage->__destruct();
        $this->assertSame($expected, $_SESSION);
    }

    public function testModifyingOneSessionObjectModifiesTheOther()
    {
        $this->storage->foo = 'bar';
        $storage = new SessionStorage();
        $storage->bar = 'foo';
        $this->assertEquals('foo', $this->storage->bar);
    }

    public function testMarkingOneSessionObjectImmutableShouldMarkOtherInstancesImmutable()
    {
        $this->storage->foo = 'bar';
        $storage = new SessionStorage();
        $this->assertEquals('bar', $storage['foo']);
        $this->storage->markImmutable();
        $this->assertTrue($storage->isImmutable(), var_export($_SESSION, 1));
    }

    public function testMultiDimensionalUnset()
    {
        $this->storage['foo'] = ['bar' => ['baz' => 'boo']];
        unset($this->storage['foo']['bar']['baz']);
        $this->assertFalse(isset($this->storage['foo']['bar']['baz']));
        unset($this->storage['foo']['bar']);
        $this->assertFalse(isset($this->storage['foo']['bar']));
    }
}
