<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Service\SessionConfigFactory;

/**
 * @group      Laminas_Session
 */
class SessionConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('Laminas\Session\Config\ConfigInterface', 'Laminas\Session\Service\SessionConfigFactory');
    }

    public function testCreatesSessionConfigByDefault()
    {
        $this->services->setService('Config', array(
            'session_config' => array(),
        ));
        $config = $this->services->get('Laminas\Session\Config\ConfigInterface');
        $this->assertInstanceOf('Laminas\Session\Config\SessionConfig', $config);
    }

    public function testCanCreateAlternateSessionConfigTypeViaConfigClassKey()
    {
        $this->services->setService('Config', array(
            'session_config' => array(
                'config_class' => 'Laminas\Session\Config\StandardConfig',
            ),
        ));
        $config = $this->services->get('Laminas\Session\Config\ConfigInterface');
        $this->assertInstanceOf('Laminas\Session\Config\StandardConfig', $config);
        // Since SessionConfig extends StandardConfig, need to test that it's not that
        $this->assertNotInstanceOf('Laminas\Session\Config\SessionConfig', $config);
    }

    public function testServiceReceivesConfiguration()
    {
        $this->services->setService('Config', array(
            'session_config' => array(
                'config_class' => 'Laminas\Session\Config\StandardConfig',
                'name'         => 'laminas',
            ),
        ));
        $config = $this->services->get('Laminas\Session\Config\ConfigInterface');
        $this->assertEquals('laminas', $config->getName());
    }
}
