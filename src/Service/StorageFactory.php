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
use Laminas\Session\Exception\ExceptionInterface as SessionException;
use Laminas\Session\Storage\Factory;
use Laminas\Session\Storage\StorageInterface;

class StorageFactory implements FactoryInterface
{
    /**
     * Create session storage object
     *
     * Uses "session_storage" section of configuration to seed a StorageInterface
     * instance. That array should contain the key "type", specifying the storage
     * type to use, and optionally "options", containing any options to be used in
     * creating the StorageInterface instance.
     *
     * @param  ServiceLocatorInterface    $services
     * @return StorageInterface
     * @throws ServiceNotCreatedException if session_storage is missing, or the
     *         factory cannot create the storage instance.
     */
    public function createService(ServiceLocatorInterface $services)
    {
        $config = $services->get('Config');
        if (!isset($config['session_storage']) || !is_array($config['session_storage'])) {
            throw new ServiceNotCreatedException(
                'Configuration is missing a "session_storage" key, or the value of that key is not an array'
            );
        }

        $config = $config['session_storage'];
        if (!isset($config['type'])) {
            throw new ServiceNotCreatedException(
                '"session_storage" configuration is missing a "type" key'
            );
        }
        $type = $config['type'];
        $options = isset($config['options']) ? $config['options'] : [];

        try {
            $storage = Factory::factory($type, $options);
        } catch (SessionException $e) {
            throw new ServiceNotCreatedException(sprintf(
                'Factory is unable to create StorageInterface instance: %s',
                $e->getMessage()
            ), $e->getCode(), $e);
        }

        return $storage;
    }
}
