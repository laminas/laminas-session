<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Session\Service;

use Zend\ServiceManager\ServiceManager;

/**
 * @group      Zend_Session
 * @covers Zend\Session\Service\SessionConfigFactory
 */
class SessionConfigFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = [
            'factories' => [
                'Zend\Session\Config\ConfigInterface' => 'Zend\Session\Service\SessionConfigFactory',
            ],
        ];
        $this->services = new ServiceManager($config);
    }

    public function testCreatesSessionConfigByDefault()
    {
        $services = $this->services->withConfig([
            'services' => [
                'config' => [
                    'session_config' => [],
                ],
            ],
        ]);
        $config = $services->get('Zend\Session\Config\ConfigInterface');
        $this->assertInstanceOf('Zend\Session\Config\SessionConfig', $config);
    }

    public function testCanCreateAlternateSessionConfigTypeViaConfigClassKey()
    {
        $services = $this->services->withConfig([
            'services' => [
                'config' => [
                    'session_config' => [
                        'config_class' => 'Zend\Session\Config\StandardConfig',
                    ],
                ],
            ],
        ]);
        $config = $services->get('Zend\Session\Config\ConfigInterface');
        $this->assertInstanceOf('Zend\Session\Config\StandardConfig', $config);
        // Since SessionConfig extends StandardConfig, need to test that it's not that
        $this->assertNotInstanceOf('Zend\Session\Config\SessionConfig', $config);
    }

    public function testServiceReceivesConfiguration()
    {
        $services = $this->services->withConfig([
            'services' => [
                'config' => [
                    'session_config' => [
                        'config_class' => 'Zend\Session\Config\StandardConfig',
                        'name'         => 'zf2',
                    ],
                ],
            ],
        ]);
        $config = $services->get('Zend\Session\Config\ConfigInterface');
        $this->assertEquals('zf2', $config->getName());
    }
}
