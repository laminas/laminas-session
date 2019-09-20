<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Session\TestAsset;

use Zend\Session\SaveHandler\SaveHandlerInterface as SaveHandler;

class TestSaveHandlerWithValidator implements SaveHandler
{
    public function open($save_path, $name)
    {
        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return '__ZF|a:1:{s:6:"_VALID";a:1:{s:47:"ZendTest\Session\TestAsset\TestFailingValidator";s:0:"";}}';
    }

    public function write($id, $data)
    {
        return true;
    }

    public function destroy($id)
    {
        return true;
    }

    public function gc($maxlifetime)
    {
        return true;
    }
}
