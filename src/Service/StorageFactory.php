<?php

namespace Laminas\Session\Service;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase

use Laminas\Session\Exception\ExceptionInterface as SessionException;
use Laminas\Session\Storage\Factory;
use Laminas\Session\Storage\StorageInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function is_array;
use function sprintf;

final class StorageFactory
{
    /**
     * Uses "session_storage" section of configuration to seed a StorageInterface
     * instance. That array should contain the key "type", specifying the storage
     * type to use, and optionally "options", containing any options to be used in
     * creating the StorageInterface instance.
     *
     * @throws RuntimeException If session_storage is missing, or the
     *         factory cannot create the storage instance.
     */
    public function __invoke(ContainerInterface $container): StorageInterface
    {
        $config = $container->get('config');
        if (! isset($config['session_storage']) || ! is_array($config['session_storage'])) {
            throw new RuntimeException(
                'Configuration is missing a "session_storage" key, or the value of that key is not an array'
            );
        }

        $config = $config['session_storage'];
        if (! isset($config['type'])) {
            throw new RuntimeException(
                '"session_storage" configuration is missing a "type" key'
            );
        }
        $type    = $config['type'];
        $options = $config['options'] ?? [];

        try {
            $storage = Factory::factory($type, $options);
        } catch (SessionException $e) {
            throw new RuntimeException(sprintf(
                'Factory is unable to create StorageInterface instance: %s',
                $e->getMessage()
            ), $e->getCode(), $e);
        }

        return $storage;
    }
}
