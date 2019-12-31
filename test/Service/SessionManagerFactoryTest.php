<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\Service;

use Laminas\ServiceManager\ServiceManager;
use Laminas\Session\Container;
use Laminas\Session\Storage\ArrayStorage;

/**
 * @group      Laminas_Session
 */
class SessionManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->services->setFactory('Laminas\Session\ManagerInterface', 'Laminas\Session\Service\SessionManagerFactory');
    }

    public function testCreatesSessionManager()
    {
        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        $this->assertInstanceOf('Laminas\Session\SessionManager', $manager);
    }

    public function testConfigObjectIsInjectedIfPresentInServices()
    {
        $config = $this->getMock('Laminas\Session\Config\ConfigInterface');
        $this->services->setService('Laminas\Session\Config\ConfigInterface', $config);
        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        $test = $manager->getConfig();
        $this->assertSame($config, $test);
    }

    public function testFactoryWillInjectStorageIfPresentInServices()
    {
        // Using concrete version here as mocking was too complex
        $storage = new ArrayStorage();
        $this->services->setService('Laminas\Session\Storage\StorageInterface', $storage);
        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        $test = $manager->getStorage();
        $this->assertSame($storage, $test);
    }

    public function testFactoryWillInjectSaveHandlerIfPresentInServices()
    {
        $saveHandler = $this->getMock('Laminas\Session\SaveHandler\SaveHandlerInterface');
        $this->services->setService('Laminas\Session\SaveHandler\SaveHandlerInterface', $saveHandler);
        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        $test = $manager->getSaveHandler();
        $this->assertSame($saveHandler, $test);
    }

    public function testFactoryWillMarkManagerAsContainerDefaultByDefault()
    {
        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        $this->assertSame($manager, Container::getDefaultManager());
    }

    public function testCanDisableContainerDefaultManagerInjectionViaConfiguration()
    {
        $config = array('session_manager' => array(
            'enable_default_container_manager' => false,
        ));
        $this->services->setService('Config', $config);
        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        $this->assertNotSame($manager, Container::getDefaultManager());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryWillAddValidatorViaConfiguration()
    {
        $config = array('session_manager' => array(
            'validators' => array(
                'Laminas\Session\Validator\RemoteAddr',
            ),
        ));
        $this->services->setService('Config', $config);
        $manager = $this->services->get('Laminas\Session\ManagerInterface');

        $manager->start();

        $this->assertEquals(1, $manager->getValidatorChain()->getListeners('session.validate')->count());
    }

    /**
     * @runInSeparateProcess
     */
    public function testStartingSessionManagerFromFactoryDoesNotTriggerUndefinedVariable()
    {
        $storage = new ArrayStorage();
        $this->services->setService('Laminas\Session\Storage\StorageInterface', $storage);

        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        $manager->start();

        $this->assertSame($storage, $manager->getStorage());
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryDoesNotOverwriteValidatorStorageValues()
    {
        $storage = new ArrayStorage();
        $storage->setMetadata('_VALID', array(
            'Laminas\Session\Validator\HttpUserAgent' => 'Foo',
            'Laminas\Session\Validator\RemoteAddr'    => '1.2.3.4',
        ));
        $this->services->setService('Laminas\Session\Storage\StorageInterface', $storage);

        $config = array(
            'session_manager' => array(
                'validators' => array(
                    'Laminas\Session\Validator\HttpUserAgent',
                    'Laminas\Session\Validator\RemoteAddr',
                ),
            ),
        );
        $this->services->setService('Config', $config);

        // This call is needed to make sure session storage data is not overwritten by the factory
        $manager = $this->services->get('Laminas\Session\ManagerInterface');

        $validatorData = $storage->getMetaData('_VALID');
        $this->assertSame('Foo', $validatorData['Laminas\Session\Validator\HttpUserAgent']);
        $this->assertSame('1.2.3.4', $validatorData['Laminas\Session\Validator\RemoteAddr']);
    }

    /**
     * @runInSeparateProcess
     */
    public function testFactoryDoesNotAttachValidatorTwoTimes()
    {
        $storage = new ArrayStorage();
        $storage->setMetadata('_VALID', array(
            'Laminas\Session\Validator\RemoteAddr' => '1.2.3.4',
        ));
        $this->services->setService('Laminas\Session\Storage\StorageInterface', $storage);

        $config = array(
            'session_manager' => array(
                'validators' => array(
                    'Laminas\Session\Validator\RemoteAddr',
                ),
            ),
        );
        $this->services->setService('Config', $config);

        $manager = $this->services->get('Laminas\Session\ManagerInterface');
        try {
            $manager->start();
        } catch (\RuntimeException $e) {
            // Ignore exception, because we are not interested whether session validation passes in this test
        }

        $this->assertEquals(1, $manager->getValidatorChain()->getListeners('session.validate')->count());
    }
}
