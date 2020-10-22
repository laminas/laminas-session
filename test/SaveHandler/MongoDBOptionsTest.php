<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\Exception\InvalidArgumentException;
use Laminas\Session\SaveHandler\MongoDBOptions;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\SaveHandler\MongoDbOptions
 */
class MongoDBOptionsTest extends TestCase
{
    public function testDefaults()
    {
        $options = new MongoDBOptions();
        self::assertNull($options->getDatabase());
        self::assertNull($options->getCollection());
        $mongoVersion       = phpversion('mongo') ?: '0.0.0';
        $defaultSaveOptions = version_compare($mongoVersion, '1.3.0', '<') ? ['safe' => true] : ['w' => 1];
        self::assertEquals($defaultSaveOptions, $options->getSaveOptions());
        self::assertEquals('name', $options->getNameField());
        self::assertEquals('data', $options->getDataField());
        self::assertEquals('lifetime', $options->getLifetimeField());
        self::assertEquals('modified', $options->getModifiedField());
    }

    public function testSetConstructor()
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

    public function testSetters()
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

    public function testInvalidDatabase()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'database' => null,
            ]
        );
    }

    public function testInvalidCollection()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'collection' => null,
            ]
        );
    }

    public function testInvalidSaveOptions()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'saveOptions' => null,
            ]
        );
    }

    public function testInvalidNameField()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'nameField' => null,
            ]
        );
    }

    public function testInvalidModifiedField()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'modifiedField' => null,
            ]
        );
    }

    public function testInvalidLifetimeField()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'lifetimeField' => null,
            ]
        );
    }

    public function testInvalidDataField()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'dataField' => null,
            ]
        );
    }
}
