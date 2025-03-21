<?php

namespace Laminas\Session\SaveHandler;

use Psr\SimpleCache\CacheInterface;
use ReturnTypeWillChange;

/**
 * Cache session save handler
 *
 * @see ReturnTypeWillChange
 */
class Cache implements SaveHandlerInterface
{
    /**
     * Session Save Path
     */
    protected string $sessionSavePath;

    /**
     * Session Name
     */
    protected string $sessionName;

    /**
     * Constructor
     */
    public function __construct(protected CacheInterface $cacheStorage)
    {
        $this->setCacheStorage($cacheStorage);
    }

    /**
     * Open Session
     */
    public function open(string $path, string $name): bool
    {
        // @todo figure out if we want to use these
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
    public function read(string $id): string
    {
        return (string) $this->getCacheStorage()->get($id);
    }

    /**
     * Write session data
     */
    public function write(string $id, string $data): bool
    {
        return $this->getCacheStorage()->set($id, $data);
    }

    /**
     * Destroy session
     */
    public function destroy(string $id): bool
    {
        if (! $this->getCacheStorage()->has($id)) {
            return true;
        }

        return $this->getCacheStorage()->delete($id);
    }

    /**
     * Garbage Collection
     */
    public function gc(int $maxlifetime): int|false
    {
        return 0;
    }

    /**
     * Set cache storage
     */
    public function setCacheStorage(CacheInterface $cacheStorage): Cache
    {
        $this->cacheStorage = $cacheStorage;
        return $this;
    }

    /**
     * Get cache storage
     */
    public function getCacheStorage(): CacheInterface
    {
        return $this->cacheStorage;
    }
}
