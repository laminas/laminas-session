<?php

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\SaveHandler\MongoDB;
use Laminas\Session\SaveHandler\MongoDBOptions;
use MongoDB\Client as MongoClient;
use MongoDB\Collection as MongoCollection;
use MongoDB\Driver\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\SaveHandler\MongoDb
 * @requires extension mongodb
 */
class MongoDBTest extends TestCase
{
    /**
     * @var MongoClient
     */
    protected $mongoClient;

    /**
     * MongoCollection instance
     *
     * @var MongoCollection
     */
    protected $mongoCollection;

    /**
     * @var MongoDBOptions
     */
    protected $options;

    /**
     * Setup performed prior to each test method
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->options = new MongoDBOptions(
            [
                'database'   => 'laminas_tests',
                'collection' => 'sessions',
            ]
        );

        $this->mongoClient     = new MongoClient();
        $this->mongoCollection = $this->mongoClient->selectCollection(
            $this->options->getDatabase(),
            $this->options->getCollection()
        );
    }

    /**
     * Tear-down operations performed after each test method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if ($this->mongoCollection) {
            $this->mongoCollection->drop();
        }
    }

    public function testReadWrite(): void
    {
        $saveHandler = new MongoDB($this->mongoClient, $this->options);
        self::assertTrue($saveHandler->open('savepath', 'sessionname'));

        $id   = '242';
        $data = ['foo' => 'bar', 'bar' => ['foo' => 'bar']];

        self::assertTrue($saveHandler->write($id, serialize($data)));
        self::assertEquals($data, unserialize($saveHandler->read($id)));

        $data = ['foo' => [1, 2, 3]];

        self::assertTrue($saveHandler->write($id, serialize($data)));
        self::assertEquals($data, unserialize($saveHandler->read($id)));
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadDestroysExpiredSession(): void
    {
        /* Note: due to the session save handler's open() method reading the
         * "session.gc_maxlifetime" INI value directly, it's necessary to set
         * that to simulate natural session expiration.
         */
        $oldMaxlifetime = ini_get('session.gc_maxlifetime');
        ini_set('session.gc_maxlifetime', 0);

        $saveHandler = new MongoDB($this->mongoClient, $this->options);
        self::assertTrue($saveHandler->open('savepath', 'sessionname'));

        $id   = '242';
        $data = ['foo' => 'bar'];

        self::assertNull($this->mongoCollection->findOne(['_id' => $id]));

        self::assertTrue($saveHandler->write($id, serialize($data)));
        self::assertNotNull($this->mongoCollection->findOne(['_id' => $id]));
        self::assertEquals('', $saveHandler->read($id));
        self::assertNull($this->mongoCollection->findOne(['_id' => $id]));

        ini_set('session.gc_maxlifetime', $oldMaxlifetime);
    }

    public function testGarbageCollection(): void
    {
        $saveHandler = new MongoDB($this->mongoClient, $this->options);
        self::assertTrue($saveHandler->open('savepath', 'sessionname'));

        $data = ['foo' => 'bar'];

        self::assertTrue($saveHandler->write(123, serialize($data)));
        self::assertTrue($saveHandler->write(456, serialize($data)));
        self::assertEquals(2, $this->mongoCollection->count());
        $saveHandler->gc(5);
        self::assertEquals(2, $this->mongoCollection->count());

        /* Note: MongoDate uses micro-second precision, so even a maximum
         * lifetime of zero would not match records that were just inserted.
         * Use a negative number instead.
         */
        $saveHandler->gc(-1);
        self::assertEquals(0, $this->mongoCollection->count());
    }

    public function testGarbageCollectionMaxlifetimeIsInSeconds(): void
    {
        $saveHandler = new MongoDB($this->mongoClient, $this->options);
        self::assertTrue($saveHandler->open('savepath', 'sessionname'));

        $data = ['foo' => 'bar'];

        self::assertTrue($saveHandler->write(123, serialize($data)));
        sleep(1);
        self::assertTrue($saveHandler->write(456, serialize($data)));
        self::assertEquals(2, $this->mongoCollection->count());

        // Clear everything what is at least 1 second old.
        $saveHandler->gc(1);
        self::assertEquals(1, $this->mongoCollection->count());

        self::assertEmpty($saveHandler->read(123));
        self::assertNotEmpty($saveHandler->read(456));
    }

    public function testWriteExceptionEdgeCaseForChangedSessionName(): void
    {
        $this->expectException(RuntimeException::class);
        $saveHandler = new MongoDB($this->mongoClient, $this->options);
        self::assertTrue($saveHandler->open('savepath', 'sessionname'));

        $id   = '242';
        $data = ['foo' => 'bar'];

        /* Note: a MongoCursorException will be thrown if a record with this ID
         * already exists with a different session name, since the upsert query
         * cannot insert a new document with the same ID and new session name.
         * This should only happen if ID's are not unique or if the session name
         * is altered mid-process.
         */
        $saveHandler->write($id, serialize($data));
        $saveHandler->open('savepath', 'sessionname_changed');
        $saveHandler->write($id, serialize($data));
    }

    public function testReadShouldAlwaysReturnString(): void
    {
        $saveHandler = new MongoDB($this->mongoClient, $this->options);
        self::assertTrue($saveHandler->open('savepath', 'sessionname'));

        $id = '242';

        $data = $saveHandler->read($id);

        self::assertTrue(is_string($data));
    }
}
