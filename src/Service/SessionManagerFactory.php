<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Session\Service;

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Container;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\StorageInterface;

class SessionManagerFactory implements FactoryInterface
{
    /**
     * Default configuration for manager behavior
     *
     * @var array
     */
    protected $defaultManagerConfig = array(
        'enable_default_container_manager' => true,
    );

    /**
     * Create session manager object
     *
     * Will consume any combination (or zero) of the following services, when
     * present, to construct the SessionManager instance:
     *
     * - Laminas\Session\Config\ConfigInterface
     * - Laminas\Session\Storage\StorageInterface
     * - Laminas\Session\SaveHandler\SaveHandlerInterface
     *
     * The first two have corresponding factories inside this namespace. The
     * last, however, does not, due to the differences in implementations, and
     * the fact that save handlers will often be written in userland. As such
     * if you wish to attach a save handler to the manager, you will need to
     * write your own factory, and assign it to the service name
     * "Laminas\Session\SaveHandler\SaveHandlerInterface", (or alias that name
     * to your own service).
     *
     * You can configure limited behaviors via the "session_manager" key of the
     * Config service. Currently, these include:
     *
     * - enable_default_container_manager: whether to inject the created instance
     *   as the default manager for Container instances. The default value for
     *   this is true; set it to false to disable.
     * - validators: ...
     *
     * @param  ServiceLocatorInterface    $services
     * @return SessionManager
     * @throws ServiceNotCreatedException if any collaborators are not of the
     *         correct type
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config        = null;
        $storage       = null;
        $saveHandler   = null;
        $managerConfig = $this->defaultManagerConfig;

        if ($services->has('Laminas\Session\Config\ConfigInterface')) {
            $config = $services->get('Laminas\Session\Config\ConfigInterface');
            if (!$config instanceof ConfigInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'SessionManager requires that the %s service implement %s; received "%s"',
                    'Laminas\Session\Config\ConfigInterface',
                    'Laminas\Session\Config\ConfigInterface',
                    (is_object($config) ? get_class($config) : gettype($config))
                ));
            }
        }

        if ($services->has('Laminas\Session\Storage\StorageInterface')) {
            $storage = $services->get('Laminas\Session\Storage\StorageInterface');
            if (!$storage instanceof StorageInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'SessionManager requires that the %s service implement %s; received "%s"',
                    'Laminas\Session\Storage\StorageInterface',
                    'Laminas\Session\Storage\StorageInterface',
                    (is_object($storage) ? get_class($storage) : gettype($storage))
                ));
            }
        }

        if ($services->has('Laminas\Session\SaveHandler\SaveHandlerInterface')) {
            $saveHandler = $services->get('Laminas\Session\SaveHandler\SaveHandlerInterface');
            if (!$saveHandler instanceof SaveHandlerInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'SessionManager requires that the %s service implement %s; received "%s"',
                    'Laminas\Session\SaveHandler\SaveHandlerInterface',
                    'Laminas\Session\SaveHandler\SaveHandlerInterface',
                    (is_object($saveHandler) ? get_class($saveHandler) : gettype($saveHandler))
                ));
            }
        }

        $manager = new SessionManager($config, $storage, $saveHandler);

        // Get session manager configuration, if any, and merge with default configuration
        if ($services->has('Config')) {
            $configService = $services->get('Config');
            if (isset($configService['session_manager'])
                && is_array($configService['session_manager'])
            ) {
                $managerConfig = array_merge($managerConfig, $configService['session_manager']);
            }
            // Attach validators to session manager, if any
            if (isset($managerConfig['validators'])) {
                $chain = $manager->getValidatorChain();
                foreach ($managerConfig['validators'] as $validator) {
                    $validator = new $validator();
                    $chain->attach('session.validate', array($validator, 'isValid'));
                }
            }
        }

        // If configuration enables the session manager as the default manager for container
        // instances, do so.
        if (isset($managerConfig['enable_default_container_manager'])
            && $managerConfig['enable_default_container_manager']
        ) {
            Container::setDefaultManager($manager);
        }

        return $manager;
    }
}
