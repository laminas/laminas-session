<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;
use ReturnTypeWillChange;

/**
 * @see ReturnTypeWillChange
 */
class TestSaveHandlerWithValidator implements SaveHandler
{
    /**
     * @param string $savePath
     * @param string $name
     * @return string
     */
    public function open($savePath, $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @param int|string $id
     * @return string
     */
    #[ReturnTypeWillChange]
    public function read($id)
    {
        return '__Laminas|a:1:{s:6:"_VALID";a:1:{s:50:"LaminasTest\Session\TestAsset\TestFailingValidator";s:0:"";}}';
    }

    /**
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function write($id, $data)
    {
        return true;
    }

    /**
     * @param int|string $id
     */
    public function destroy($id): bool
    {
        return true;
    }

    /**
     * @param int $maxlifetime
     * @return bool
     */
    #[ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
        return true;
    }
}
