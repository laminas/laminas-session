<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;

class TestSaveHandlerWithValidator implements SaveHandler
{
    /**
     * @param string $savePath
     * @param string $name
     * @return string
     */
    public function open($savePath, $name)
    {
        return true;
    }

    /** @return bool */
    public function close()
    {
        return true;
    }

    /**
     * @param int|string $id
     * @return string
     */
    public function read($id)
    {
        return '__Laminas|a:1:{s:6:"_VALID";a:1:{s:50:"LaminasTest\Session\TestAsset\TestFailingValidator";s:0:"";}}';
    }

    /**
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function write($id, $data)
    {
        return true;
    }

    /**
     * @param int|string $id
     * @return bool
     */
    public function destroy($id)
    {
        return true;
    }

    /**
     * @param int $maxlifetime
     * @return bool
     */
    public function gc($maxlifetime)
    {
        return true;
    }
}
