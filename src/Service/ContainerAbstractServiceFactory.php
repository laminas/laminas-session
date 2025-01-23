<?php

namespace Laminas\Session\Service;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Container;
use Laminas\Session\Exception\RuntimeException;
use Laminas\Session\ManagerInterface;
use Psr\Container\ContainerInterface;

use function array_change_key_case;
use function array_flip;
use function array_key_exists;
use function get_debug_type;
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
class ContainerAbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * Cached container configuration
     */
    protected ?array $config = null;

    /**
     * Configuration key in which session containers live
     */
    protected string $configKey = 'session_containers';

    protected ?ManagerInterface $sessionManager = null;

    /**
     * Can we create an instance of the given service? (v3 usage).
     */
    public function canCreate(ContainerInterface $container, string $requestedName): bool
    {
        $config = $this->getConfig($container);
        if ($config === []) {
            return false;
        }

        $containerName = $this->normalizeContainerName($requestedName);
        return array_key_exists($containerName, $config);
    }

    /**
     * Can we create an instance of the given service? (v2 usage)
     *
     * @psalm-suppress PossiblyUnusedMethod,PossiblyUnusedParam
     */
    public function canCreateServiceWithName(
        ServiceLocatorInterface $container,
        string $name,
        string $requestedName
    ): bool {
        return $this->canCreate($container, $requestedName);
    }

    /**
     * Create and return a named container (v3 usage).
     */
    public function __invoke(ContainerInterface $container, string $requestedName, ?array $options = null): Container
    {
        $manager = $this->getSessionManager($container);
        return new Container($requestedName, $manager);
    }

    /**
     * Create and return a named container (v2 usage).
     *
     * @psalm-suppress PossiblyUnusedMethod,PossiblyUnusedParam
     */
    public function createServiceWithName(
        ServiceLocatorInterface $container,
        string $name,
        string $requestedName
    ): Container {
        return $this($container, $requestedName);
    }

    /**
     * Retrieve config from service locator, and cache for later
     */
    protected function getConfig(ContainerInterface $container): array
    {
        if (is_array($this->config)) {
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
    protected function getSessionManager(ContainerInterface $container): ?ManagerInterface
    {
        if ($this->sessionManager instanceof ManagerInterface) {
            return $this->sessionManager;
        }

        if ($container->has(ManagerInterface::class)) {
            $sessionManager = $container->get(ManagerInterface::class);

            if (! $sessionManager instanceof ManagerInterface) {
                throw new RuntimeException(sprintf(
                    '%s service did not map to a %s implementation; received %s',
                    ManagerInterface::class,
                    ManagerInterface::class,
                    get_debug_type($sessionManager)
                ));
            }

            $this->sessionManager = $sessionManager;
        }

        return $this->sessionManager;
    }

    /**
     * Normalize the container name in order to perform a lookup
     */
    protected function normalizeContainerName(string $name): string
    {
        return strtolower($name);
    }
}
