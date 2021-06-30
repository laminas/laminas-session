<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Sessin\Storage\StorageInterface;
use Laminas\Session\AbstractManager;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Storage\ArrayStorage;

class TestManager extends AbstractManager
{
    /** @var bool */
    public $started = false;

    /**
     * @var string
     * @psalm-var class-string<ConfigInterface>
     */
    protected $configDefaultClass = StandardConfig::class;

    /**
     * @var string
     * @psalm-var class-string<StorageInterface>
     */
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

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
    }

    public function getId()
    {
    }

    /**
     * @param string|int $id
     * @return void
     */
    public function setId($id)
    {
    }

    public function regenerateId()
    {
    }

    /**
     * @param null|int $ttl
     * @return void
     */
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
