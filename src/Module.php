<?php

declare(strict_types=1);

namespace Laminas\Session;

use Laminas\ServiceManager\ServiceManager;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
class Module
{
    /**
     * Retrieve default laminas-session config for laminas-mvc context.
     *
     * @return array{'service_manager': ServiceManagerConfiguration, 'validators': ServiceManagerConfiguration}
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();
        return [
            'service_manager' => $provider->getDependencyConfig(),
            'validators'      => $provider->getValidatorConfig(),
        ];
    }
}
