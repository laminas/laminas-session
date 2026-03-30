<?php

namespace Laminas\Session;

/**
 * @deprecated This class will be removed without replacement in version 3.0.
 */
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
            'validators'      => $provider->getValidatorConfig(),
        ];
    }
}
