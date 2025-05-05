<?php

namespace Laminas\Session\Service;

use Laminas\Session\Container;
use Laminas\Session\ManagerInterface;
use Psr\Container\ContainerInterface;

use function assert;

final class ContainerFactory
{
    public function __invoke(ContainerInterface $container): Container
    {
        $manager = $container->get(ManagerInterface::class);
        assert($manager instanceof ManagerInterface);
        return new Container(
            Container::class,
            $manager
        );
    }
}
