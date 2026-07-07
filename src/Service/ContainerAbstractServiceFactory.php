<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase

use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\Session\AbstractContainer;
use Laminas\Session\Container;
use Laminas\Session\Exception\InvalidArgumentException;
use Laminas\Session\ManagerInterface;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function is_a;
use function is_array;
use function is_int;
use function is_string;
use function sprintf;
use function strtolower;

/**
 * Session container abstract service factory.
 *
 * Allows creating AbstractContainer instances, using the ManagerInterface
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
 * $container     = $services->get('MySessionContainer');
 * $lazyContainer = $services->get('MyLazyContainer');
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

    private string $defaultClassKey = Container::class;

    private string $defaultContainerClass = Container::class;

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
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): AbstractContainer {
        $config  = $this->getConfig($container);
        $class   = $config[strtolower($requestedName)];
        $manager = $this->getSessionManager($container);

        return new $class($requestedName, $manager);
    }

    /**
     * Retrieve config from service locator, and cache for later
     */
    private function getConfig(ContainerInterface $container): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $this->config = [];

        if (! $container->has('config')) {
            return $this->config;
        }

        $config = $container->get('config');

        if (
            ! is_array($config)
            || ! isset($config[$this->configKey])
            || ! is_array($config[$this->configKey])
        ) {
            return $this->config;
        }

        $configuredContainers = $config[$this->configKey];

        if (
            isset($configuredContainers[$this->defaultClassKey])
            && is_string($configuredContainers[$this->defaultClassKey])
        ) {
            $this->defaultContainerClass = $this->resolveClass($configuredContainers[$this->defaultClassKey]);
        }

        foreach ($configuredContainers as $key => $value) {
            // Do not allow overwriting the default Container
            if ($key === $this->defaultClassKey) {
                continue;
            }

            if (is_int($key) && is_string($value)) {
                $name  = strtolower($value);
                $class = $this->defaultContainerClass;
            } elseif (is_string($key) && is_string($value)) {
                $name  = strtolower($key);
                $class = $this->resolveClass($value);
            } else {
                continue;
            }

            $this->config[$name] = $class;
        }

        return $this->config;
    }

    /**
     * @return class-string<AbstractContainer>
     * @throws InvalidArgumentException
     */
    private function resolveClass(string $class): string
    {
        if (! is_a($class, AbstractContainer::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Container class "%s" is invalid; must be a subclass of %s',
                $class,
                AbstractContainer::class
            ));
        }

        return $class;
    }

    /**
     * Retrieve the session manager instance, if any.
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
