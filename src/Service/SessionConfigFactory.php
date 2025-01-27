<?php

namespace Laminas\Session\Service;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Config\SameSiteCookieCapableInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SaveHandler\SaveHandlerInterface;

use function class_exists;
use function get_debug_type;
use function is_array;
use function sprintf;

class SessionConfigFactory implements FactoryInterface
{
    /**
     * Create session configuration object (v3 usage).
     *
     * Uses "session_config" section of configuration to seed a ConfigInterface
     * instance. By default, Laminas\Session\Config\SessionConfig will be used, but
     * you may also specify a specific implementation variant using the
     * "config_class" subkey.
     *
     * @param string $requestedName
     * @param null|array $options
     * @return ConfigInterface
     * @throws ServiceNotCreatedException If session_config is missing, or an
     *     invalid config_class is used.
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        if (! isset($config['session_config']) || ! is_array($config['session_config'])) {
            throw new ServiceNotCreatedException(
                'Configuration is missing a "session_config" key, or the value of that key is not an array'
            );
        }

        $class = SessionConfig::class;

        /** @var array{
         *     config_class?: string,
         *     save_handler?: string|SaveHandlerInterface,
         *     cookie_samesite: string
         * } $config
         */
        $config = $config['session_config'];
        if (isset($config['config_class'])) {
            if (! class_exists($config['config_class'])) {
                throw new ServiceNotCreatedException(sprintf(
                    'Invalid configuration class "%s" specified in "config_class" session configuration; '
                    . 'must be a valid class',
                    $config['config_class']
                ));
            }
            $class = $config['config_class'];
            unset($config['config_class']);
        }

        // We set SaveHandlerInterface as default save_handler if it exists in the container, we do this
        // because SessionManagerFactory does this, and this keeps the configuration consistent
        if (! isset($config['save_handler']) && $container->has(SaveHandlerInterface::class)) {
            $config['save_handler'] = SaveHandlerInterface::class;
        }

        if (isset($config['save_handler']) && $config['save_handler'] === SaveHandlerInterface::class) {
            if (! $container->has($config['save_handler'])) {
                throw new ServiceNotCreatedException(sprintf(
                    'Class %s set as save_handler must be defined in the service manager',
                    $config['save_handler']
                ));
            }

            $saveHandler = $container->get($config['save_handler']);
            if (! $saveHandler instanceof SaveHandlerInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'Class %s set as save_handler must implement %s; received "%s"',
                    $saveHandler::class,
                    SaveHandlerInterface::class,
                    get_debug_type($saveHandler)
                ));
            }

            $config['save_handler'] = $saveHandler;
        }

        $sessionConfig = new $class();
        if (! $sessionConfig instanceof ConfigInterface) {
            throw new ServiceNotCreatedException(sprintf(
                'Invalid configuration class "%s" specified in "config_class" session configuration; must implement %s',
                $class,
                ConfigInterface::class
            ));
        }

        if (
            isset($config['cookie_samesite'])
            && ! $sessionConfig instanceof SameSiteCookieCapableInterface
        ) {
            throw new ServiceNotCreatedException(sprintf(
                'Invalid configuration class "%s". When configuration option "cookie_samesite" is used,'
                . ' the configuration class must implement %s',
                $class,
                SameSiteCookieCapableInterface::class
            ));
        }
        $sessionConfig->setOptions($config);

        return $sessionConfig;
    }

    /**
     * @deprecated This method will be removed in version 3.0
     * Create and return a config instance (v2 usage).
     *
     * @param null|string $canonicalName
     * @param string $requestedName
     * @return ConfigInterface
     */
    public function createService(
        ServiceLocatorInterface $services,
        $canonicalName = null,
        $requestedName = ConfigInterface::class
    ) {
        return $this($services, $requestedName);
    }
}
