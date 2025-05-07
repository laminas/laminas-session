<?php

declare(strict_types=1);

namespace Laminas\Session\SaveHandler;

use Laminas\Session\Exception\SimpleCacheInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

use function hash;

/**
 * Cache session save handler
 */
final class Cache implements SaveHandlerInterface
{
    public function __construct(private readonly CacheInterface $cacheStorage)
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
        try {
            if (! $this->cacheStorage->has($this->getCacheKey($id))) {
                return false;
            }
            return (string) $this->cacheStorage->get($this->getCacheKey($id), '');
        } catch (InvalidArgumentException $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Write session data
     */
    public function write(string $id, string $data): bool
    {
        try {
            return $this->cacheStorage->set($this->getCacheKey($id), $data);
        } catch (InvalidArgumentException $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Destroy session
     */
    public function destroy(string $id): bool
    {
        try {
            if (! $this->cacheStorage->has($this->getCacheKey($id))) {
                return true;
            }
            return $this->cacheStorage->delete($this->getCacheKey($id));
        } catch (InvalidArgumentException $exception) {
            throw new SimpleCacheInvalidArgumentException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Garbage Collection
     */
    public function gc(int $maxlifetime): int|false
    {
        return 0;
    }

    private function getCacheKey(string $id): string
    {
        return hash('xxh32', $id);
    }
}
