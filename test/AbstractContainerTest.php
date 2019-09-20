<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session;

use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Zend\Session\Config\StandardConfig;
use Zend\Session\ManagerInterface as Manager;
use ZendTest\Session\TestAsset\TestContainer;

/**
 * @group      Zend_Session
 * @covers \Zend\Session\AbstractContainer
 */
class AbstractContainerTest extends TestCase
{
    /**
     * Hack to allow running tests in separate processes
     *
     * @see    http://matthewturland.com/2010/08/19/process-isolation-in-phpunit/
     */
    protected $preserveGlobalState = false;

    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @var Container
     */
    protected $container;

    protected function setUp()
    {
        $_SESSION = [];
        Container::setDefaultManager(null);

        $config = new StandardConfig([
            'storage' => 'Zend\\Session\\Storage\\ArrayStorage',
        ]);

        $this->manager = $manager = new TestAsset\TestManager($config);
        $this->container = new TestContainer('Default', $manager);
    }

    protected function tearDown()
    {
        $_SESSION = [];
        Container::setDefaultManager(null);
    }

    /**
     * This test case fails on zend-session 2.8.0 with the php error below and works fine on 2.7.*.
     * "Only variable references should be returned by reference"
     */
    public function testOffsetGetMissingKey()
    {
        self::assertNull($this->container->offsetGet('this key does not exist in the container'));
    }
}
