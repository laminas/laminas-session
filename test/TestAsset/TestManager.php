<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session\TestAsset;

use Zend\EventManager\EventManagerInterface;
use Zend\Session\AbstractManager;
use Zend\Session\Configuration\StandardConfig;
use Zend\Session\Storage\ArrayStorage;

class TestManager extends AbstractManager
{
    public $started = false;

    protected $configDefaultClass = StandardConfig::class;
    protected $storageDefaultClass = ArrayStorage::class;

    public function start()
    {
        $this->started = true;
    }

    public function destroy()
    {
        $this->started = false;
    }

    public function stop()
    {
    }

    public function writeClose()
    {
        $this->started = false;
    }

    public function getName()
    {
    }

    public function setName($name)
    {
    }

    public function getId()
    {
    }

    public function setId($id)
    {
    }

    public function regenerateId()
    {
    }

    public function rememberMe($ttl = null)
    {
    }

    public function forgetMe()
    {
    }

    public function setValidatorChain(EventManagerInterface $chain)
    {
    }

    public function getValidatorChain()
    {
    }

    public function isValid()
    {
    }

    public function sessionExists()
    {
    }

    public function expireSessionCookie()
    {
    }
}
