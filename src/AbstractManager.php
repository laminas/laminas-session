<?php

declare(strict_types=1);

namespace Laminas\Session;

use Laminas\Session\Config\ConfigInterface as Config;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\ManagerInterface as Manager;
use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\Storage\StorageInterface as Storage;
use Laminas\Session\Validator\ValidatorInterface;

use function class_exists;
use function sprintf;

/**
 * Base ManagerInterface implementation
 *
 * Defines common constructor logic and getters for Storage and Configuration
 */
abstract class AbstractManager implements Manager
{
    protected Config $config;

    /**
     * Default configuration class to use when no configuration provided
     */
    protected string $defaultConfigClass = SessionConfig::class;

    protected Storage $storage;

    /**
     * Default storage class to use when no storage provided
     */
    protected string $defaultStorageClass = SessionArrayStorage::class;

    protected SaveHandler|null $saveHandler = null;

    /**
     * Constructor
     *
     * @throws Exception\RuntimeException
     */
    public function __construct(
        ?Config $config = null,
        ?Storage $storage = null,
        ?SaveHandler $saveHandler = null,
        /** @var list<class-string<ValidatorInterface>> */
        protected array $validators = []
    ) {
        // init config
        if ($config === null) {
            if (! class_exists($this->defaultConfigClass)) {
                throw new Exception\RuntimeException(sprintf(
                    'Unable to locate config class "%s"; class does not exist',
                    $this->defaultConfigClass
                ));
            }

            $config = new $this->defaultConfigClass();

            if (! $config instanceof Config) {
                throw new Exception\RuntimeException(sprintf(
                    'Default config class %s is invalid; must implement %s\Config\ConfigInterface',
                    $this->defaultConfigClass,
                    __NAMESPACE__
                ));
            }
        }

        $this->config = $config;

        // init storage
        if ($storage === null) {
            if (! class_exists($this->defaultStorageClass)) {
                throw new Exception\RuntimeException(sprintf(
                    'Unable to locate storage class "%s"; class does not exist',
                    $this->defaultStorageClass
                ));
            }

            $storage = new $this->defaultStorageClass();

            if (! $storage instanceof Storage) {
                throw new Exception\RuntimeException(sprintf(
                    'Default storage class %s is invalid; must implement %s\Storage\StorageInterface',
                    $this->defaultConfigClass,
                    __NAMESPACE__
                ));
            }
        }

        $this->storage = $storage;

        // save handler
        if ($saveHandler !== null) {
            $this->saveHandler = $saveHandler;
        }
    }

    /**
     * Set configuration object
     */
    public function setConfig(Config $config): static
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Retrieve configuration object
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Set session storage object
     */
    public function setStorage(Storage $storage): static
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Retrieve storage object
     */
    public function getStorage(): Storage
    {
        return $this->storage;
    }

    /**
     * Set session save handler object
     */
    public function setSaveHandler(SaveHandler $saveHandler): static
    {
        $this->saveHandler = $saveHandler;
        return $this;
    }

    /**
     * Get SaveHandler Object
     */
    public function getSaveHandler(): SaveHandler|null
    {
        return $this->saveHandler;
    }
}
