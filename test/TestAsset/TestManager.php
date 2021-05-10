<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Session\AbstractManager;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Storage\ArrayStorage;

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

    public function stop(): void
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
