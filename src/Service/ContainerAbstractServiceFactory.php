<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Session\Service;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\Container;

/**
 * Session container abstract service factory.
 *
 * Allows creating Container instances, using the Zend\Service\ManagerInterface
 * if present. Containers are named in a "session_containers" array in the
 * Config service:
 *
 * <code>
 * return array(
 *     'session_containers' => array(
 *         'SessionContainer\sample',
 *         'my_sample_session_container',
 *         'MySessionContainer',
 *     ),
 * );
 * </code>
 *
 * <code>
 * $container = $services->get('MySessionContainer');
 * </code>
 */
class ContainerAbstractServiceFactory implements AbstractFactoryInterface
{
    /**
     * Cached container configuration
     *
     * @var array
     */
    protected $config;

    /**
     * Configuration key in which session containers live
     *
     * @var string
     */
    protected $configKey = 'session_containers';

    /**
     * @var \Zend\Session\ManagerInterface
     */
    protected $sessionManager;

    /**
     * @param  ContainerInterface      $container
     * @param  string                  $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ContainerInterface $container, $requestedName)
    {
        $config = $this->getConfig($container);
        if (empty($config)) {
            return false;
        }

        $containerName = $this->normalizeContainerName($requestedName);
        return array_key_exists($containerName, $config);
    }

    /**
     * @param  ContainerInterface      $container
     * @param  string                  $requestedName
     * @return Container
     */

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $manager = $this->getSessionManager($container);
        return new Container($requestedName, $manager);
    }

    /**
     * Retrieve config from service locator, and cache for later
     *
     * @param  ContainerInterface $container
     * @return false|array
     */
    protected function getConfig(ContainerInterface $container)
    {
        if (null !== $this->config) {
            return $this->config;
        }

        if (!$container->has('config')) {
            $this->config = [];
            return $this->config;
        }

        $config = $container->get('config');
        if (!isset($config[$this->configKey]) || !is_array($config[$this->configKey])) {
            $this->config = [];
            return $this->config;
        }

        $config = $config[$this->configKey];
        $config = array_flip($config);

        $this->config = array_change_key_case($config);

        return $this->config;
    }

    /**
     * Retrieve the session manager instance, if any
     *
     * @param ContainerInterface $container
     * @return null|\Zend\Session\ManagerInterface
     */
    protected function getSessionManager(ContainerInterface $container)
    {
        if ($this->sessionManager !== null) {
            return $this->sessionManager;
        }

        if ($container->has('Zend\Session\ManagerInterface')) {
            $this->sessionManager = $container->get('Zend\Session\ManagerInterface');
        }

        return $this->sessionManager;
    }

    /**
     * Normalize the container name in order to perform a lookup
     *
     * @param  string $name
     * @return string
     */
    protected function normalizeContainerName($name)
    {
        return strtolower($name);
    }
}
