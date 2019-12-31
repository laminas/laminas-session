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

class SessionConfigFactory implements FactoryInterface
{
    /**
     * Create session configuration object
     *
     * Uses "session_config" section of configuration to seed a ConfigInterface
     * instance. By default, Laminas\Session\Config\SessionConfig will be used, but
     * you may also specify a specific implementation variant using the
     * "config_class" subkey.
     *
     * @param  ServiceLocatorInterface    $services
     * @return ConfigInterface
     * @throws ServiceNotCreatedException if session_config is missing, or an
     *         invalid config_class is used
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = $services->get('Config');
        if (!isset($config['session_config']) || !is_array($config['session_config'])) {
            throw new ServiceNotCreatedException(
                'Configuration is missing a "session_config" key, or the value of that key is not an array'
            );
        }
        $class  = 'Laminas\Session\Config\SessionConfig';
        $config = $config['session_config'];
        if (isset($config['config_class'])) {
            if (!class_exists($config['config_class'])) {
                throw new ServiceNotCreatedException(sprintf(
                    'Invalid configuration class "%s" specified in "config_class" session configuration; must be a valid class',
                    $class
                ));
            }
            $class = $config['config_class'];
            unset($config['config_class']);
        }

        $sessionConfig = new $class();
        if (!$sessionConfig instanceof ConfigInterface) {
            throw new ServiceNotCreatedException(sprintf(
                'Invalid configuration class "%s" specified in "config_class" session configuration; must implement Laminas\Session\Config\ConfigInterface',
                $class
            ));
        }
        $sessionConfig->setOptions($config);

        return $sessionConfig;
    }
}
