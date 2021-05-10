<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\DbTableGateway;

class TestDbTableGatewaySaveHandler extends DbTableGateway
{
    protected $numReadCalls = 0;

    protected $numDestroyCalls = 0;

    public function getNumReadCalls(): int
    {
        return $this->numReadCalls;
    }

    public function getNumDestroyCalls(): int
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
