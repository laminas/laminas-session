<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Validator\RemoteAddr;
use Psr\Container\ContainerInterface;

use function assert;
use function is_array;
use function is_iterable;
use function iterator_to_array;

/**
 * @internal
 *
 * @psalm-internal Laminas\Session
 * @psalm-internal LaminasTest\Session
 * @psalm-import-type OptionsArgument from RemoteAddr
 */
final class RemoteAddressFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): RemoteAddr {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = is_iterable($config) ? iterator_to_array($config) : [];
        if (! isset($config['session_manager'])) {
            throw new ServiceNotCreatedException(
                'Configuration is missing a "session_manager" key, or the value of that key is not an array'
            );
        }
        /** @psalm-var array<string, mixed> $sessionOptions */
        $sessionOptions = $config['session_manager'];
        $validators     = $sessionOptions['validators'] ?? [];
        assert(is_array($validators));
        $validatorsOptions = $validators['options'] ?? [];
        assert(is_array($validatorsOptions));
        /** @var OptionsArgument $options */
        $options = $validatorsOptions['remote_addr'] ?? [];
        return new RemoteAddr($options);
    }
}
