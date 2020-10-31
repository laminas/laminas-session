<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\SaveHandler\Cache;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit testing for DbTable include all tests for
 * regular session handling
 *
 * @covers \Laminas\Session\SaveHandler\Cache
 */
class CacheTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var CacheAdapter
     */
    protected $cache;

    /**
     * @var array
     */
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
        $cacheStorage = $this->prophesize('Laminas\Cache\Storage\StorageInterface');
        $cacheStorage->setItem('242', Argument::type('string'))
            ->will(
                function ($args) {
                    $this->getItem('242')->willReturn($args[1]);
                    return true;
                }
            );
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage->reveal());

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $data = unserialize($saveHandler->read($id));
        self::assertEquals(
            $this->testArray,
            $data,
            'Expected ' . var_export($this->testArray, 1) . "\nbut got: " . var_export($data, 1)
        );
    }

    public function testReadWriteComplex(): void
    {
        $cacheStorage = $this->prophesize('Laminas\Cache\Storage\StorageInterface');
        $cacheStorage->setItem('242', Argument::type('string'))
            ->will(
                function ($args) {
                    $this->getItem('242')->willReturn($args[1]);
                    return true;
                }
            );
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage->reveal());
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    public function testReadWriteTwice(): void
    {
        $cacheStorage = $this->prophesize('Laminas\Cache\Storage\StorageInterface');
        $cacheStorage->setItem('242', Argument::type('string'))
            ->will(
                function ($args) {
                    $this->getItem('242')->willReturn($args[1])->shouldBeCalledTimes(2);
                    return true;
                }
            )
            ->shouldBeCalledTimes(2);
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage->reveal());

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    public function testReadShouldAlwaysReturnString(): void
    {
        $cacheStorage = $this->prophesize('Laminas\Cache\Storage\StorageInterface');
        $cacheStorage->getItem('242')->willReturn(null);
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage->reveal());

        $id = '242';

        $data = $saveHandler->read($id);

        self::assertTrue(is_string($data));
    }

    public function testDestroyReturnsTrueEvenWhenSessionDoesNotExist(): void
    {
        $cacheStorage             = $this->prophesize('Laminas\Cache\Storage\StorageInterface');
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage->reveal());

        $id = '242';

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }

    public function testDestroyReturnsTrueWhenSessionIsDeleted(): void
    {
        $cacheStorage = $this->prophesize('Laminas\Cache\Storage\StorageInterface');
        $cacheStorage->setItem('242', Argument::type('string'))
            ->will(
                function ($args) {
                    $this->getItem('242', Argument::any())
                        ->will(
                            function ($args) {
                                $return =& $args[1];
                                return $return;
                            }
                        );
                    return true;
                }
            );
        $this->usedSaveHandlers[] = $saveHandler = new Cache($cacheStorage->reveal());

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }
}
