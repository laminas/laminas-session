<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;
use ReturnTypeWillChange;

/**
 * @see ReturnTypeWillChange
 */
class TestSaveHandler implements SaveHandler
{
    #[ReturnTypeWillChange]
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @param int|string $id
     * @return void
     */
    #[ReturnTypeWillChange]
    public function read($id)
    {
    }

    /**
     * @param int|string $id
     * @param array $data
     */
    public function write($id, $data): bool
    {
        return false;
    }

    /**
     * @param int|string $id
     */
    public function destroy($id): bool
    {
        return false;
    }

    /**
     * @param int $maxlifetime
     * @return void
     */
    #[ReturnTypeWillChange]
    public function gc($maxlifetime)
    {
    }
}
