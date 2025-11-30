<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Validator\RemoteAddr;
use Psr\Container\ContainerInterface;

use function is_array;

/** @psalm-import-type OptionsArgument from RemoteAddr */
final class RemoteAddressFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): RemoteAddr {
        $config = $container->get('config');
        if (! isset($config['session_manager']) || ! is_array($config['session_manager'])) {
            throw new ServiceNotCreatedException(
                'Configuration is missing a "session_manager" key, or the value of that key is not an array'
            );
        }

        $config = $config['session_manager'];
        /** @var OptionsArgument $options */
        $options = $config['options'] ?? [];

        return new RemoteAddr($options);
    }
}
