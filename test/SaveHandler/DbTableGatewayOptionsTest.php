<?php

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
    public function testDefaults(): void
    {
        $options = new DbTableGatewayOptions();
        self::assertEquals('id', $options->getIdColumn());
        self::assertEquals('name', $options->getNameColumn());
        self::assertEquals('modified', $options->getModifiedColumn());
        self::assertEquals('lifetime', $options->getLifetimeColumn());
        self::assertEquals('data', $options->getDataColumn());
    }

    public function testSetConstructor(): void
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

    public function testSetters(): void
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

    public function testInvalidIdColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'idColumn' => null,
            ]
        );
    }

    public function testInvalidNameColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'nameColumn' => null,
            ]
        );
    }

    public function testInvalidModifiedColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'modifiedColumn' => null,
            ]
        );
    }

    public function testInvalidLifetimeColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'lifetimeColumn' => null,
            ]
        );
    }

    public function testInvalidDataColumn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $options = new DbTableGatewayOptions(
            [
                'dataColumn' => null,
            ]
        );
    }
}
