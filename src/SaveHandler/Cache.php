<?php

namespace Laminas\Session\SaveHandler;

use Psr\SimpleCache\CacheInterface;

/**
 * Cache session save handler
 */
final class Cache implements SaveHandlerInterface
{
    public function __construct(private CacheInterface $cacheStorage)
    {
    }

    /**
     * Open Session
     */
    public function open(string $path, string $name): bool
    {
        return true;
    }

    /**
     * Close session
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * Read session data
     */
    public function read(string $id): string|false
    {
        if (! $this->cacheStorage->has($id)) {
            return false;
        }
        return (string) $this->cacheStorage->get($id, '');
    }

    /**
     * Write session data
     */
    public function write(string $id, string $data): bool
    {
        return $this->cacheStorage->set($id, $data);
    }

    /**
     * Destroy session
     */
    public function destroy(string $id): bool
    {
        if (! $this->cacheStorage->has($id)) {
            return true;
        }

        return $this->cacheStorage->delete($id);
    }

    /**
     * Garbage Collection
     */
    public function gc(int $maxlifetime): int|false
    {
        return 0;
    }
}
