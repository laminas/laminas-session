<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;

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
        return '__Laminas|a:1:{s:6:"_VALID";a:1:{s:50:"LaminasTest\Session\TestAsset\TestFailingValidator";s:0:"";}}';
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
