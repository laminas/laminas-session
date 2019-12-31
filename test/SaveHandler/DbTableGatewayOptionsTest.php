<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\SaveHandler;

use Laminas\Session\SaveHandler\DbTableGatewayOptions;

/**
 * Unit testing for DbTableGatewayOptions
 *
 * @category   Laminas
 * @package    Laminas_Session
 * @subpackage UnitTests
 * @group      Laminas_Session
 */
class DbTableGatewayOptionsTest extends \PHPUnit_Framework_TestCase
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
        $options = new DbTableGatewayOptions(array(
            'idColumn' => 'testId',
            'nameColumn' => 'testName',
            'modifiedColumn' => 'testModified',
            'lifetimeColumn' => 'testLifetime',
            'dataColumn' => 'testData',
        ));

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

    /**
     * @expectedException Laminas\Session\Exception\InvalidArgumentException
     */
    public function testInvalidIdColumn()
    {
        $options = new DbTableGatewayOptions(array(
            'idColumn' => null,
        ));
    }

    /**
     * @expectedException Laminas\Session\Exception\InvalidArgumentException
     */
    public function testInvalidNameColumn()
    {
        $options = new DbTableGatewayOptions(array(
            'nameColumn' => null,
        ));
    }

    /**
     * @expectedException Laminas\Session\Exception\InvalidArgumentException
     */
    public function testInvalidModifiedColumn()
    {
        $options = new DbTableGatewayOptions(array(
            'modifiedColumn' => null,
        ));
    }

    /**
     * @expectedException Laminas\Session\Exception\InvalidArgumentException
     */
    public function testInvalidLifetimeColumn()
    {
        $options = new DbTableGatewayOptions(array(
            'lifetimeColumn' => null,
        ));
    }

    /**
     * @expectedException Laminas\Session\Exception\InvalidArgumentException
     */
    public function testInvalidDataColumn()
    {
        $options = new DbTableGatewayOptions(array(
            'dataColumn' => null,
        ));
    }
}
