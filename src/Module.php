<?php

namespace Laminas\Session;

use Laminas\ServiceManager\Factory\InvokableFactory;

class Module
{
    /**
     * Retrieve default laminas-session config for laminas-mvc context.
     *
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();
        return [
            'service_manager' => $provider->getDependencyConfig(),
            'validators'      => [
                'factories' => [
                    Validator\Csrf::class => InvokableFactory::class,
                ],
                'aliases'   => [
                    'csrf' => Validator\Csrf::class,
                ],
            ],
        ];
    }
}
