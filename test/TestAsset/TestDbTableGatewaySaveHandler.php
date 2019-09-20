<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session\TestAsset;

use Zend\Session\SaveHandler\DbTableGateway;

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
