<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SaveHandler\DbTableGateway;
use Laminas\Session\SaveHandler\DbTableGatewayOptions;
use LaminasTest\Session\TestAsset\TestDbTableGatewaySaveHandler;
use PHPUnit\Framework\TestCase;

/**
 * Unit testing for DbTableGateway include all tests for
 * regular session handling
 *
 * @covers \Laminas\Session\SaveHandler\DbTableGateway
 */
abstract class DbTableGatewayTest extends TestCase
{
    /**
     * @var Adapter
     */
    protected $adapter;

    /**
     * @var TableGateway
     */
    protected $tableGateway;

    /**
     * @var DbTableGatewayOptions
     */
    protected $options;

    /**
     * Array to collect used DbTableGateway objects, so they are not
     * destroyed before all tests are done and session is not closed
     *
     * @var array
     */
    protected $usedSaveHandlers = [];

    /**
     * Test data container.
     *
     * @var array
     */
    private $testArray;

    /**
     * @return Adapter
     */
    abstract protected function getAdapter();

    /**
     * Setup performed prior to each test method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->adapter = $this->getAdapter();

        $this->options = new DbTableGatewayOptions(
            [
                'nameColumn'     => 'name',
                'idColumn'       => 'id',
                'dataColumn'     => 'data',
                'modifiedColumn' => 'modified',
                'lifetimeColumn' => 'lifetime',
            ]
        );

        $this->setupDb($this->options);
        $this->testArray = ['foo' => 'bar', 'bar' => ['foo' => 'bar']];
    }

    /**
     * Tear-down operations performed after each test method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->adapter) {
            $this->dropTable();
        }
    }

    public function testReadWrite()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $data = unserialize($saveHandler->read($id));
        self::assertEquals(
            $this->testArray,
            $data,
            'Expected ' . var_export($this->testArray, 1) . "\nbut got: " . var_export($data, 1)
        );
    }

    public function testReadWriteComplex()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));
    }

    public function testReadWriteTwice()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        self::assertEquals($this->testArray, unserialize($saveHandler->read($id)));

        $updateData = $this->testArray + ['time' => microtime(true)];
        self::assertTrue($saveHandler->write($id, serialize($updateData)));

        self::assertEquals($updateData, unserialize($saveHandler->read($id)));
    }

    public function testReadShouldAlwaysReturnString()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        $data = $saveHandler->read($id);

        self::assertTrue(is_string($data));
    }

    public function testDestroyReturnsTrueEvenWhenSessionDoesNotExist()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }

    public function testDestroyReturnsTrueWhenSessionIsDeleted()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '242';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        $result = $saveHandler->destroy($id);

        self::assertTrue($result);
    }

    public function testReadDestroysExpiredSession()
    {
        $this->usedSaveHandlers[] = $saveHandler = new DbTableGateway($this->tableGateway, $this->options);
        $saveHandler->open('savepath', 'sessionname');

        $id = '345';

        self::assertTrue($saveHandler->write($id, serialize($this->testArray)));

        // set lifetime to 0
        $query = <<<EOD
UPDATE sessions
    SET {$this->options->getLifetimeColumn()} = 0
WHERE
    {$this->options->getIdColumn()} = {$id}
    AND {$this->options->getNameColumn()} = 'sessionname'
EOD;
        $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);

        // check destroy session
        $result = $saveHandler->read($id);
        self::assertEquals($result, '');

        // check if the record really has been deleted
        $result = $this->adapter->query(
            "
                SELECT {$this->options->getIdColumn()}
                FROM sessions
                WHERE {$this->options->getIdColumn()} = {$id}
            ",
            Adapter::QUERY_MODE_EXECUTE
        );

        self::assertEquals(0, $result->count());

        // cleans the test record from the db
        $this->adapter->query(
            "DELETE FROM sessions WHERE {$this->options->getIdColumn()} = {$id};",
            Adapter::QUERY_MODE_EXECUTE
        );
    }

    /**
     * Sets up the database connection and creates the table for session data
     *
     * @param DbTableGatewayOptions $options
     * @return void
     */
    protected function setupDb(DbTableGatewayOptions $options)
    {
        $query = <<<EOD
CREATE TABLE sessions (
    {$options->getIdColumn()} int NOT NULL,
    {$options->getNameColumn()} varchar(255) NOT NULL,
    {$options->getModifiedColumn()} int default NULL,
    {$options->getLifetimeColumn()} int default NULL,
    {$options->getDataColumn()} text,
    PRIMARY KEY ({$options->getIdColumn()}, {$options->getNameColumn()})
);
EOD;
        $this->adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        $this->tableGateway = new TableGateway('sessions', $this->adapter);
    }

    /**
     * Drops the database table for session data
     *
     * @return void
     */
    protected function dropTable()
    {
        if (! $this->adapter) {
            return;
        }
        $this->adapter->query('DROP TABLE sessions', Adapter::QUERY_MODE_EXECUTE);
    }
}
