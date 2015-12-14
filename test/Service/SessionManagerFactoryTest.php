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
use Zend\Session\Container;
use Zend\Session\Storage\ArrayStorage;

/**
 * @group      Zend_Session
 * @covers Zend\Session\Service\SessionManagerFactory
 */
class SessionManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = [
            'factories' => [
                'Zend\Session\ManagerInterface' => 'Zend\Session\Service\SessionManagerFactory',
            ],
        ];
        $this->services = new ServiceManager($config);
    }

    public function testCreatesSessionManager()
    {
        $manager = $this->services->get('Zend\Session\ManagerInterface');
        $this->assertInstanceOf('Zend\Session\SessionManager', $manager);
    }

    public function testConfigObjectIsInjectedIfPresentInServices()
    {
        $config = $this->getMock('Zend\Session\Config\ConfigInterface');
        $services = $this->services->withConfig([
            'services' => [
                'Zend\Session\Config\ConfigInterface' => $config,
            ],
        ]);
        $manager = $services->get('Zend\Session\ManagerInterface');
        $test = $manager->getConfig();
        $this->assertSame($config, $test);
    }

    public function testFactoryWillInjectStorageIfPresentInServices()
    {
        // Using concrete version here as mocking was too complex
        $storage = new ArrayStorage();
        $services = $this->services->withConfig([
            'services' => [
                'Zend\Session\Storage\StorageInterface' => $storage,
            ],
        ]);
        $manager = $services->get('Zend\Session\ManagerInterface');
        $test = $manager->getStorage();
        $this->assertSame($storage, $test);
    }

    public function testFactoryWillInjectSaveHandlerIfPresentInServices()
    {
        $saveHandler = $this->getMock('Zend\Session\SaveHandler\SaveHandlerInterface');
        $services = $this->services->withConfig([
            'services' => [
                'Zend\Session\SaveHandler\SaveHandlerInterface' => $saveHandler,
            ],
        ]);
        $manager = $services->get('Zend\Session\ManagerInterface');
        $test = $manager->getSaveHandler();
        $this->assertSame($saveHandler, $test);
    }

    public function testFactoryWillMarkManagerAsContainerDefaultByDefault()
    {
        $manager = $this->services->get('Zend\Session\ManagerInterface');
        $this->assertSame($manager, Container::getDefaultManager());
    }

    public function testCanDisableContainerDefaultManagerInjectionViaConfiguration()
    {
        $config = ['session_manager' => [
            'enable_default_container_manager' => false,
        ]];
        $services = $this->services->withConfig([
            'services' => [
                'config' => $config,
            ],
        ]);
        $manager = $services->get('Zend\Session\ManagerInterface');
        $this->assertNotSame($manager, Container::getDefaultManager());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryWillAddValidatorViaConfiguration()
    {
        $config = ['session_manager' => [
            'validators' => [
                'Zend\Session\Validator\RemoteAddr',
            ],
        ]];
        $services = $this->services->withConfig([
            'services' => [
                'config' => $config,
            ],
        ]);
        $manager = $services->get('Zend\Session\ManagerInterface');

        $manager->start();

        $chain = $manager->getValidatorChain();
        $r = new \ReflectionMethod($chain, 'getListenersByEventName');
        $r->setAccessible(true);
        $this->assertEquals(1, count($r->invoke($chain, 'session.validate')));
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartingSessionManagerFromFactoryDoesNotTriggerUndefinedVariable()
    {
        $storage = new ArrayStorage();
        $services = $this->services->withConfig([
            'services' => [
                'Zend\Session\Storage\StorageInterface' => $storage
            ],
        ]);

        $manager = $services->get('Zend\Session\ManagerInterface');
        $manager->start();

        $this->assertSame($storage, $manager->getStorage());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryDoesNotOverwriteValidatorStorageValues()
    {
        $storage = new ArrayStorage();
        $storage->setMetadata('_VALID', [
            'Zend\Session\Validator\HttpUserAgent' => 'Foo',
            'Zend\Session\Validator\RemoteAddr'    => '1.2.3.4',
        ]);
        $services = $this->services->withConfig([
            'services' => [
                'Zend\Session\Storage\StorageInterface' => $storage,
                'config' => [
                    'session_manager' => [
                        'validators' => [
                            'Zend\Session\Validator\HttpUserAgent',
                            'Zend\Session\Validator\RemoteAddr',
                        ],
                    ],
                ],
            ],
        ]);

        // This call is needed to make sure session storage data is not overwritten by the factory
        $manager = $services->get('Zend\Session\ManagerInterface');

        $validatorData = $storage->getMetaData('_VALID');
        $this->assertSame('Foo', $validatorData['Zend\Session\Validator\HttpUserAgent']);
        $this->assertSame('1.2.3.4', $validatorData['Zend\Session\Validator\RemoteAddr']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryDoesNotAttachValidatorTwoTimes()
    {
        $storage = new ArrayStorage();
        $storage->setMetadata('_VALID', [
            'Zend\Session\Validator\RemoteAddr' => '1.2.3.4',
        ]);
        $services = $this->services->withConfig([
            'services' => [
                'Zend\Session\Storage\StorageInterface' => $storage,
                'config' => [
                    'session_manager' => [
                        'validators' => [
                            'Zend\Session\Validator\RemoteAddr',
                        ],
                    ],
                ],
            ],
        ]);

        $manager = $services->get('Zend\Session\ManagerInterface');
        try {
            $manager->start();
        } catch (\RuntimeException $e) {
            // Ignore exception, because we are not interested whether session validation passes in this test
        }

        $chain = $manager->getValidatorChain();
        $r = new \ReflectionMethod($chain, 'getListenersByEventName');
        $r->setAccessible(true);
        $this->assertEquals(1, count($r->invoke($chain, 'session.validate')));
    }
}
