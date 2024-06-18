<?php

namespace Laminas\Session;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
class ConfigProvider
{
    /**
     * Retrieve configuration for laminas-session.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'validators'   => $this->getValidatorConfig(),
        ];
    }

    /**
     * Retrieve dependency config for laminas-session.
     *
     * @return ServiceManagerConfiguration
     */
    public function getDependencyConfig()
    {
        return [
            'abstract_factories' => [
                Service\ContainerAbstractServiceFactory::class,
            ],
            'aliases'            => [
                SessionManager::class => ManagerInterface::class,

                // Legacy Zend Framework aliases
                'Zend\Session\SessionManager'           => SessionManager::class,
                'Zend\Session\Config\ConfigInterface'   => Config\ConfigInterface::class,
                'Zend\Session\ManagerInterface'         => ManagerInterface::class,
                'Zend\Session\Storage\StorageInterface' => Storage\StorageInterface::class,
            ],
            'factories'          => [
                Config\ConfigInterface::class   => Service\SessionConfigFactory::class,
                ManagerInterface::class         => Service\SessionManagerFactory::class,
                Storage\StorageInterface::class => Service\StorageFactory::class,
            ],
        ];
    }

    /** @return ServiceManagerConfiguration */
    public function getValidatorConfig(): array
    {
        return [
            'factories' => [
                Validator\Csrf::class => InvokableFactory::class,
            ],
            'aliases'   => [
                'csrf' => Validator\Csrf::class,
            ],
        ];
    }
}
