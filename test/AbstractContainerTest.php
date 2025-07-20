<?php

declare(strict_types=1);

namespace LaminasTest\Session;

use Laminas\Session\AbstractContainer;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Container;
use Laminas\Session\ManagerInterface as Manager;
use LaminasTest\Session\TestAsset\TestContainer;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Laminas\Session\AbstractContainer
 */
class AbstractContainerTest extends TestCase
{
    protected Manager $manager;
    protected AbstractContainer $container;

    protected function setUp(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);

        $config = new StandardConfig();

        $this->manager   = $manager = new TestAsset\TestManager($config);
        $this->container = new TestContainer('Default', $manager);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        Container::setDefaultManager(null);
    }

    #[IgnoreDeprecations]
    public function testOffsetGetMissingKey(): void
    {
        self::assertNull($this->container->offsetGet('this key does not exist in the container'));
    }
}
