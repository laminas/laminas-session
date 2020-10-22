<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

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
        $this->assertNull($options->getDatabase());
        $this->assertNull($options->getCollection());
        $mongoVersion = phpversion('mongo') ?: '0.0.0';
        $defaultSaveOptions = version_compare($mongoVersion, '1.3.0', '<') ? ['safe' => true] : ['w' => 1];
        $this->assertEquals($defaultSaveOptions, $options->getSaveOptions());
        $this->assertEquals('name', $options->getNameField());
        $this->assertEquals('data', $options->getDataField());
        $this->assertEquals('lifetime', $options->getLifetimeField());
        $this->assertEquals('modified', $options->getModifiedField());
    }

    public function testSetConstructor()
    {
        $options = new MongoDBOptions([
            'database' => 'testDatabase',
            'collection' => 'testCollection',
            'saveOptions' => ['w' => 2],
            'nameField' => 'testName',
            'dataField' => 'testData',
            'lifetimeField' => 'testLifetime',
            'modifiedField' => 'testModified',
        ]);

        $this->assertEquals('testDatabase', $options->getDatabase());
        $this->assertEquals('testCollection', $options->getCollection());
        $this->assertEquals(['w' => 2], $options->getSaveOptions());
        $this->assertEquals('testName', $options->getNameField());
        $this->assertEquals('testData', $options->getDataField());
        $this->assertEquals('testLifetime', $options->getLifetimeField());
        $this->assertEquals('testModified', $options->getModifiedField());
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

        $this->assertEquals('testDatabase', $options->getDatabase());
        $this->assertEquals('testCollection', $options->getCollection());
        $this->assertEquals(['w' => 2], $options->getSaveOptions());
        $this->assertEquals('testName', $options->getNameField());
        $this->assertEquals('testData', $options->getDataField());
        $this->assertEquals('testLifetime', $options->getLifetimeField());
        $this->assertEquals('testModified', $options->getModifiedField());
    }

    public function testInvalidDatabase()
    {
        $this->expectException(\Laminas\Session\Exception\InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'database' => null,
            ]
        );
    }

    public function testInvalidCollection()
    {
        $this->expectException(\Laminas\Session\Exception\InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'collection' => null,
            ]
        );
    }

    public function testInvalidSaveOptions()
    {
        $this->expectException(\Laminas\Session\Exception\InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'saveOptions' => null,
            ]
        );
    }

    public function testInvalidNameField()
    {
        $this->expectException(\Laminas\Session\Exception\InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'nameField' => null,
            ]
        );
    }

    public function testInvalidModifiedField()
    {
        $this->expectException(\Laminas\Session\Exception\InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'modifiedField' => null,
            ]
        );
    }

    public function testInvalidLifetimeField()
    {
        $this->expectException(\Laminas\Session\Exception\InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'lifetimeField' => null,
            ]
        );
    }

    public function testInvalidDataField()
    {
        $this->expectException(\Laminas\Session\Exception\InvalidArgumentException::class);
        $options = new MongoDBOptions(
            [
                'dataField' => null,
            ]
        );
    }
}
