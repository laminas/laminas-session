<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;

class TestSaveHandler implements SaveHandler
{
    /**
     * @param string $savePath
     * @param string $name
     * @return void
     */
    public function open($savePath, $name)
    {
    }

    public function close()
    {
    }

    /**
     * @param int|string $id
     * @return void
     */
    public function read($id)
    {
    }

    /**
     * @param int|string $id
     * @param array $data
     * @return void
     */
    public function write($id, $data)
    {
    }

    /**
     * @param int|string $id
     * @return void
     */
    public function destroy($id)
    {
    }

    /**
     * @param int $maxlifetime
     * @return void
     */
    public function gc($maxlifetime)
    {
    }
}
