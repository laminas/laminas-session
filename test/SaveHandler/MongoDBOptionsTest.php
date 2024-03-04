<?php

declare(strict_types=1);

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\Exception\InvalidArgumentException;
use Laminas\Session\SaveHandler\MongoDBOptions;
use PHPUnit\Framework\TestCase;

use function getenv;
use function phpversion;
use function version_compare;

/**
 * @covers \Laminas\Session\SaveHandler\MongoDbOptions
 */
class MongoDBOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $enabled = (bool) getenv('TESTS_LAMINAS_SESSION_ADAPTER_DRIVER_MONGODB');
        if (! $enabled) {
            $this->markTestSkipped('MongoDB tests are disabled');
        }

        $options = new MongoDBOptions();
        self::assertNull($options->getDatabase());
        self::assertNull($options->getCollection());
        $mongoVersion       = phpversion('mongo');
        $mongoVersion       = $mongoVersion === false ? '0.0.0' : $mongoVersion;
        $defaultSaveOptions = version_compare($mongoVersion, '1.3.0', '<') ? ['safe' => true] : ['w' => 1];
        self::assertEquals($defaultSaveOptions, $options->getSaveOptions());
        self::assertEquals('name', $options->getNameField());
        self::assertEquals('data', $options->getDataField());
        self::assertEquals('lifetime', $options->getLifetimeField());
        self::assertEquals('modified', $options->getModifiedField());
    }

    public function testSetConstructor(): void
    {
        $options = new MongoDBOptions(
            [
                'database'      => 'testDatabase',
                'collection'    => 'testCollection',
                'saveOptions'   => ['w' => 2],
                'nameField'     => 'testName',
                'dataField'     => 'testData',
                'lifetimeField' => 'testLifetime',
                'modifiedField' => 'testModified',
            ]
        );

        self::assertEquals('testDatabase', $options->getDatabase());
        self::assertEquals('testCollection', $options->getCollection());
        self::assertEquals(['w' => 2], $options->getSaveOptions());
        self::assertEquals('testName', $options->getNameField());
        self::assertEquals('testData', $options->getDataField());
        self::assertEquals('testLifetime', $options->getLifetimeField());
        self::assertEquals('testModified', $options->getModifiedField());
    }

    public function testSetters(): void
    {
        $options = new MongoDBOptions();
        $options->setDatabase('testDatabase')
            ->setCollection('testCollection')
            ->setSaveOptions(['w' => 2])
            ->setNameField('testName')
            ->setDataField('testData')
            ->setLifetimeField('testLifetime')
            ->setModifiedField('testModified');

        self::assertEquals('testDatabase', $options->getDatabase());
        self::assertEquals('testCollection', $options->getCollection());
        self::assertEquals(['w' => 2], $options->getSaveOptions());
        self::assertEquals('testName', $options->getNameField());
        self::assertEquals('testData', $options->getDataField());
        self::assertEquals('testLifetime', $options->getLifetimeField());
        self::assertEquals('testModified', $options->getModifiedField());
    }

    public function testInvalidDatabase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MongoDBOptions(
            [
                'database' => null,
            ]
        );
    }

    public function testInvalidCollection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MongoDBOptions(
            [
                'collection' => null,
            ]
        );
    }

    public function testInvalidSaveOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MongoDBOptions(
            [
                'saveOptions' => null,
            ]
        );
    }

    public function testInvalidNameField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MongoDBOptions(
            [
                'nameField' => null,
            ]
        );
    }

    public function testInvalidModifiedField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MongoDBOptions(
            [
                'modifiedField' => null,
            ]
        );
    }

    public function testInvalidLifetimeField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MongoDBOptions(
            [
                'lifetimeField' => null,
            ]
        );
    }

    public function testInvalidDataField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MongoDBOptions(
            [
                'dataField' => null,
            ]
        );
    }
}
