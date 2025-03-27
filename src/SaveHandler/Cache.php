<?php

namespace Laminas\Session\SaveHandler;

use Psr\SimpleCache\CacheInterface;

/**
 * Cache session save handler
 */
final class Cache implements SaveHandlerInterface
{
    public function __construct(
        private CacheInterface $cacheStorage,
        private string $sessionSavePath,
        private string $sessionName
    ) {
    }

    /**
     * Open Session
     */
    public function open(string $path, string $name): bool
    {
        $this->sessionSavePath = $path;
        $this->sessionName     = $name;

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
        return $this->cacheStorage->get($id, false);
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
