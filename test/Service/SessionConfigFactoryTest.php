<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Service\SessionConfigFactory;

/**
 * @group      Laminas_Session
 * @covers Laminas\Session\Service\SessionConfigFactory
 */
class SessionConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = new Config([
            'factories' => [
                ConfigInterface::class => SessionConfigFactory::class,
            ],
        ]);
        $this->services = new ServiceManager();
        $config->configureServiceManager($this->services);
    }

    public function testCreatesSessionConfigByDefault()
    {
        $this->services->setService('config', [
            'session_config' => [],
        ]);
        $config = $this->services->get(ConfigInterface::class);
        $this->assertInstanceOf(SessionConfig::class, $config);
    }

    public function testCanCreateAlternateSessionConfigTypeViaConfigClassKey()
    {
        $this->services->setService('config', [
            'session_config' => [
                'config_class' => StandardConfig::class,
            ],
        ]);
        $config = $this->services->get(ConfigInterface::class);
        $this->assertInstanceOf(StandardConfig::class, $config);
        // Since SessionConfig extends StandardConfig, need to assert not SessionConfig
        $this->assertNotInstanceOf(SessionConfig::class, $config);
    }

    public function testServiceReceivesConfiguration()
    {
        $this->services->setService('config', [
            'session_config' => [
                'config_class' => StandardConfig::class,
                'name'         => 'laminas',
            ],
        ]);
        $config = $this->services->get(ConfigInterface::class);
        $this->assertEquals('laminas', $config->getName());
    }
}
