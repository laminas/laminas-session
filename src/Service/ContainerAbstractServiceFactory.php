<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\Session\Container;
use Laminas\Session\ManagerInterface;
use Psr\Container\ContainerInterface;

use function array_change_key_case;
use function array_flip;
use function array_key_exists;
use function is_array;
use function strtolower;

/**
 * Session container abstract service factory.
 *
 * Allows creating Container instances, using the ManagerInterface
 * if present. Containers are named in a "session_containers" array in the
 * Config service:
 *
 * <code>
 * return array(
 *     'session_containers' => array(
 *         'SessionContainer\sample',
 *         'my_sample_session_container',
 *         'MySessionContainer',
 *     ),
 * );
 * </code>
 *
 * <code>
 * $container = $services->get('MySessionContainer');
 * </code>
 */
final class ContainerAbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * Cached container configuration
     */
    private ?array $config = null;

    /**
     * Configuration key in which session containers live
     */
    private string $configKey = 'session_containers';

    private ?ManagerInterface $sessionManager = null;

    /**
     * Can we create an instance of the given service?
     */
    public function canCreate(ContainerInterface $container, string $requestedName): bool
    {
        $config = $this->getConfig($container);
        if ($config === []) {
            return false;
        }

        return array_key_exists(strtolower($requestedName), $config);
    }

    /**
     * Create and return a named container.
     */
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): Container
    {
        $manager = $this->getSessionManager($container);
        return new Container($requestedName, $manager);
    }

    /**
     * Retrieve config from service locator, and cache for later
     */
    private function getConfig(ContainerInterface $container): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        if (! $container->has('config')) {
            $this->config = [];
            return $this->config;
        }

        $config = $container->get('config');
        if (! isset($config[$this->configKey]) || ! is_array($config[$this->configKey])) {
            $this->config = [];
            return $this->config;
        }

        $config = $config[$this->configKey];
        $config = array_flip($config);

        $this->config = array_change_key_case($config);

        return $this->config;
    }

    /**
     * Retrieve the session manager instance, if any
     */
    private function getSessionManager(ContainerInterface $container): ?ManagerInterface
    {
        if ($this->sessionManager !== null) {
            return $this->sessionManager;
        }

        if ($container->has(ManagerInterface::class)) {
            $sessionManager = $container->get(ManagerInterface::class);

            if ($sessionManager instanceof ManagerInterface) {
                $this->sessionManager = $sessionManager;
            }
        }

        return $this->sessionManager;
    }
}
