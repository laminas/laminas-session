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
final class TestSaveHandlerWithValidator implements SaveHandler
{
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
