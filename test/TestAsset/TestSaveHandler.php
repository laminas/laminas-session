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
    #[\ReturnTypeWillChange]
    public function open($savePath, $name): void
    {
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @param int|string $id
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function read($id)
    {
    }

    /**
     * @param int|string $id
     * @param array $data
     * @return void
     */
    public function write($id, $data): bool
    {
        return false;
    }

    /**
     * @param int|string $id
     * @return void
     */
    public function destroy($id): bool
    {
        return false;
    }

    /**
     * @param int $maxlifetime
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
    }
}
