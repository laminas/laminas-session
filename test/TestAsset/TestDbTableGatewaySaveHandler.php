<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\DbTableGateway;

class TestDbTableGatewaySaveHandler extends DbTableGateway
{
    /** @var int */
    protected $numReadCalls = 0;

    /** @var int */
    protected $numDestroyCalls = 0;

    public function getNumReadCalls(): int
    {
        return $this->numReadCalls;
    }

    public function getNumDestroyCalls(): int
    {
        return $this->numDestroyCalls;
    }

    /**
     * @param int|string $id
     * @param bool $destroyExpired Optional; true by default
     * @return string
     */
    public function read($id, $destroyExpired = true)
    {
        $this->numReadCalls++;
        return parent::read($id, $destroyExpired);
    }

    /**
     * @param int|string $id
     * @return bool
     */
    public function destroy($id)
    {
        $this->numDestroyCalls++;
        return parent::destroy($id);
    }
}
