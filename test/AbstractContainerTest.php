<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session;

use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\ManagerInterface as Manager;
use LaminasTest\Session\TestAsset\TestContainer;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\AbstractContainer
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

    protected function setUp(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);

        $config = new StandardConfig(
            [
                'storage' => 'Laminas\\Session\\Storage\\ArrayStorage',
            ]
        );

        $this->manager   = $manager = new TestAsset\TestManager($config);
        $this->container = new TestContainer('Default', $manager);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);
    }

    /**
     * This test case fails on laminas-session 2.8.0 with the php error below and works fine on 2.7.*.
     * "Only variable references should be returned by reference"
     */
    public function testOffsetGetMissingKey()
    {
        self::assertNull($this->container->offsetGet('this key does not exist in the container'));
    }
}
