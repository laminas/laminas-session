<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\DbTableGateway;

class TestDbTableGatewaySaveHandler extends DbTableGateway
{
    protected $numReadCalls = 0;

    protected $numDestroyCalls = 0;
    
    public function getNumReadCalls()
    {
        return $this->numReadCalls;
    }
    
    public function getNumDestroyCalls()
    {
        return $this->numDestroyCalls;
    }

    public function read($id, $destroyExpired = true)
    {
        $this->numReadCalls++;
        return parent::read($id, $destroyExpired);
    }
    
    public function destroy($id)
    {
        $this->numDestroyCalls++;
        return parent::destroy($id);
    }
}