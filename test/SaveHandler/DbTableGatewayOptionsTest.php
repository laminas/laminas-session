<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\Exception\InvalidArgumentException;
use Laminas\Session\SaveHandler\DbTableGatewayOptions;
use PHPUnit\Framework\TestCase;

/**
 * Unit testing for DbTableGatewayOptions
 *
 * @covers \Laminas\Session\SaveHandler\DbTableGatewayOptions
 */
class DbTableGatewayOptionsTest extends TestCase
{
    public function testDefaults()
    {
        $options = new DbTableGatewayOptions();
        $this->assertEquals('id', $options->getIdColumn());
        $this->assertEquals('name', $options->getNameColumn());
        $this->assertEquals('modified', $options->getModifiedColumn());
        $this->assertEquals('lifetime', $options->getLifetimeColumn());
        $this->assertEquals('data', $options->getDataColumn());
    }

    public function testSetConstructor()
    {
        $options = new DbTableGatewayOptions([
            'idColumn'       => 'testId',
            'nameColumn'     => 'testName',
            'modifiedColumn' => 'testModified',
            'lifetimeColumn' => 'testLifetime',
            'dataColumn'     => 'testData',
        ]);

        $this->assertEquals('testId', $options->getIdColumn());
        $this->assertEquals('testName', $options->getNameColumn());
        $this->assertEquals('testModified', $options->getModifiedColumn());
        $this->assertEquals('testLifetime', $options->getLifetimeColumn());
        $this->assertEquals('testData', $options->getDataColumn());
    }

    public function testSetters()
    {
        $options = new DbTableGatewayOptions();
        $options->setIdColumn('testId')
            ->setNameColumn('testName')
            ->setModifiedColumn('testModified')
            ->setLifetimeColumn('testLifetime')
            ->setDataColumn('testData');

        $this->assertEquals('testId', $options->getIdColumn());
        $this->assertEquals('testName', $options->getNameColumn());
        $this->assertEquals('testModified', $options->getModifiedColumn());
        $this->assertEquals('testLifetime', $options->getLifetimeColumn());
        $this->assertEquals('testData', $options->getDataColumn());
    }

    public function testInvalidIdColumn()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'idColumn' => null,
            ]
        );
    }

    public function testInvalidNameColumn()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'nameColumn' => null,
            ]
        );
    }

    public function testInvalidModifiedColumn()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'modifiedColumn' => null,
            ]
        );
    }

    public function testInvalidLifetimeColumn()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'lifetimeColumn' => null,
            ]
        );
    }

    public function testInvalidDataColumn()
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'dataColumn' => null,
            ]
        );
    }
}
