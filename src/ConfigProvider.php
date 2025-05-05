<?php

namespace Laminas\Session;

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
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories' => [
                Config\ConfigInterface::class   => Service\SessionConfigFactory::class,
                ManagerInterface::class         => Service\SessionManagerFactory::class,
                Storage\StorageInterface::class => Service\StorageFactory::class,
                Container::class                => Service\ContainerFactory::class,
            ],
        ];
    }

    public function getValidatorConfig(): array
    {
        return [
            'factories' => [
                Validator\Csrf::class => Service\CsrfValidatorFactory::class,
            ],
            'aliases'   => [
                'csrf' => Validator\Csrf::class,
            ],
        ];
    }
}
