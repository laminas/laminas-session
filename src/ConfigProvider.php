<?php

declare(strict_types=1);

namespace Laminas\Session;

use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Service\EnvironmentFactoryInterface;
use Laminas\Session\Service\GlobalEnvironmentFactory;
use Laminas\Session\Service\RemoteAddressFactory;
use Laminas\Session\Validator\HttpUserAgent;
use Laminas\Session\Validator\Id;
use Laminas\Session\Validator\RemoteAddr;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class ConfigProvider
{
    /**
     * Retrieve configuration for laminas-session.
     *
     * @return array{'dependencies': ServiceManagerConfiguration, 'validators': ServiceManagerConfiguration}
     */
    public function __invoke(): array
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
    public function getDependencyConfig(): array
    {
        return [
            'abstract_factories' => [
                Service\ContainerAbstractServiceFactory::class,
            ],
            'aliases'            => [
                SessionManager::class              => ManagerInterface::class,
                EnvironmentFactoryInterface::class => GlobalEnvironmentFactory::class,

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
                GlobalEnvironmentFactory::class => InvokableFactory::class,
                Id::class                       => InvokableFactory::class,
                HttpUserAgent::class            => InvokableFactory::class,
                RemoteAddr::class               => RemoteAddressFactory::class,
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
