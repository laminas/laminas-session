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
        self::assertEquals('id', $options->getIdColumn());
        self::assertEquals('name', $options->getNameColumn());
        self::assertEquals('modified', $options->getModifiedColumn());
        self::assertEquals('lifetime', $options->getLifetimeColumn());
        self::assertEquals('data', $options->getDataColumn());
    }

    public function testSetConstructor()
    {
        $options = new DbTableGatewayOptions(
            [
                'idColumn'       => 'testId',
                'nameColumn'     => 'testName',
                'modifiedColumn' => 'testModified',
                'lifetimeColumn' => 'testLifetime',
                'dataColumn'     => 'testData',
            ]
        );

        self::assertEquals('testId', $options->getIdColumn());
        self::assertEquals('testName', $options->getNameColumn());
        self::assertEquals('testModified', $options->getModifiedColumn());
        self::assertEquals('testLifetime', $options->getLifetimeColumn());
        self::assertEquals('testData', $options->getDataColumn());
    }

    public function testSetters()
    {
        $options = new DbTableGatewayOptions();
        $options->setIdColumn('testId')
            ->setNameColumn('testName')
            ->setModifiedColumn('testModified')
            ->setLifetimeColumn('testLifetime')
            ->setDataColumn('testData');

        self::assertEquals('testId', $options->getIdColumn());
        self::assertEquals('testName', $options->getNameColumn());
        self::assertEquals('testModified', $options->getModifiedColumn());
        self::assertEquals('testLifetime', $options->getLifetimeColumn());
        self::assertEquals('testData', $options->getDataColumn());
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
