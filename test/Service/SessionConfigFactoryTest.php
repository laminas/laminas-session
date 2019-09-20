<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session\Service;

use PHPUnit\Framework\TestCase;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\ServiceManager;
use Zend\Session\Config\ConfigInterface;
use Zend\Session\Config\SessionConfig;
use Zend\Session\Config\StandardConfig;
use Zend\Session\Service\SessionConfigFactory;

/**
 * @group      Zend_Session
 * @covers \Zend\Session\Service\SessionConfigFactory
 */
class SessionConfigFactoryTest extends TestCase
{
    protected function setUp()
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
                'name'         => 'zf2',
            ],
        ]);
        $config = $this->services->get(ConfigInterface::class);
        $this->assertEquals('zf2', $config->getName());
    }
}
