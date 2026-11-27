<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;
use ReturnTypeWillChange;

use function bin2hex;
use function random_bytes;

/**
 * @see ReturnTypeWillChange
 */
final class TestSaveHandler implements SaveHandler
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

    // phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function create_sid(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function validateId(string $id): bool
    {
        return true;
    }
}
