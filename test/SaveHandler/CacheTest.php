<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Cache\Storage\Adapter\AdapterInterface as CacheAdapter;
use Laminas\Cache\StorageFactory as CacheFactory;
use Laminas\Session\SaveHandler\Cache;

/**
 * Unit testing for DbTable include all tests for
 * regular session handling
 *
 * @category   Laminas
 * @package    Laminas_Session
 * @subpackage UnitTests
 * @group      Laminas_Session
 * @group      Laminas_Cache
 */
class CacheTest extends \PHPUnit_Framework_TestCase
{
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
    protected $usedSaveHandlers = array();

    public function setUp()
    {
        $this->cache = CacheFactory::adapterFactory('memory', array('memory_limit' => 0));
        $this->testArray = array('foo' => 'bar', 'bar' => array('foo' => 'bar'));
    }

    public function testReadWrite()
    {
        $this->usedSaveHandlers[] = $saveHandler = new Cache($this->cache);

        $id = '242';

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $data = unserialize($saveHandler->read($id));
        $this->assertEquals($this->testArray, $data, 'Expected ' . var_export($this->testArray, 1) . "\nbut got: " . var_export($data, 1));
    }

    public function testReadWriteComplex()
    {
        $this->usedSaveHandlers[] = $saveHandler = new Cache($this->cache);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $this->assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    public function testReadWriteTwice()
    {
        $this->usedSaveHandlers[] = $saveHandler = new Cache($this->cache);

        $id = '242';

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $this->assertEquals($this->testArray, unserialize($saveHandler->read($id)));

        $this->assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $this->assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }
}
