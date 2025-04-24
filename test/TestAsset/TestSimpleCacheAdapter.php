<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use DateInterval;
use DateTimeImmutable;
use Laminas\Session\Exception\SimpleCacheException;
use Laminas\Session\Exception\SimpleCacheInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

use function gettype;
use function is_int;
use function is_string;
use function preg_match;
use function preg_quote;
use function sprintf;
use function strlen;
use function var_export;

class TestSimpleCacheAdapter implements CacheInterface
{
    public const INVALID_KEY_CHARS = ':@{}()/\\';

    /** @var array<string, mixed> $storage*/
    private array $storage        = [];
    private int $maximumKeyLength = 64;

    private int $defaultTtl = 3600;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertValidKey($key);

        try {
            $result = $this->storage[$key];
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        }

        return $result ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $this->assertValidKey($key);
        $ttl = $this->convertTtlToInteger($ttl);

        if ($ttl === null) {
            $ttl = $this->defaultTtl;
        }

        // PSR-16 states that 0 or negative TTL values should result in cache
        // invalidation for the item.
        if (1 > $ttl) {
            return $this->delete($key);
        }

        try {
            $this->storage[$key] = $value;
            return true;
        } catch (Throwable $e) {
            throw static::translateThrowable($e);
        }
    }

    public function delete(string $key): bool
    {
        $this->assertValidKey($key);

        try {
            unset($this->storage[$key]);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function assertValidKey(string|int $key): void
    {
        if ('' === $key) {
            throw new SimpleCacheInvalidArgumentException(
                'Invalid key provided; cannot be empty'
            );
        }

        if (0 === $key) {
            // cache/integration-tests erroneously tests that ['0' => 'value']
            // is a valid payload to setMultiple(). However, PHP silently
            // converts '0' to 0, which would normally be invalid.
            return;
        }

        if (! is_string($key)) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid key provided of type "%s"%s; must be a string',
                gettype($key),
                sprintf(' (%s)', var_export($key, true))
            ));
        }

        $regex = sprintf('/[%s]/', preg_quote(self::INVALID_KEY_CHARS, '/'));
        if (preg_match($regex, $key)) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid key "%s" provided; cannot contain any of (%s)',
                $key,
                self::INVALID_KEY_CHARS
            ));
        }

        if (preg_match('/^.{' . ($this->maximumKeyLength + 1) . ',}/u', $key)) {
            throw new SimpleCacheInvalidArgumentException(sprintf(
                'Invalid key provided; string length must be at most %d characters, %d characters given',
                $this->maximumKeyLength,
                strlen($key)
            ));
        }
    }

    private static function translateThrowable(
        Throwable $throwable
    ): SimpleCacheInvalidArgumentException|SimpleCacheException {
        $exceptionClass = $throwable instanceof InvalidArgumentException
            ? SimpleCacheInvalidArgumentException::class
            : SimpleCacheException::class;

        return new $exceptionClass($throwable->getMessage(), $throwable->getCode(), $throwable);
    }

    private function convertTtlToInteger(int|DateInterval|null $ttl): int|null
    {
        // null === absence of a TTL
        if (null === $ttl) {
            return null;
        }

        // integers are always okay
        if (is_int($ttl)) {
            return $ttl;
        }

        $now = new DateTimeImmutable();
        $end = $now->add($ttl);
        return $end->getTimestamp() - $now->getTimestamp();
    }

    public function clear(): bool
    {
        // method is not used in Laminas\Session\SaveHandler\Cache
        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        // method is not used in Laminas\Session\SaveHandler\Cache
        return [];
    }

    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        // method is not used in Laminas\Session\SaveHandler\Cache
        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        // method is not used in Laminas\Session\SaveHandler\Cache
        return true;
    }

    public function has(string $key): bool
    {
        // method is not used in Laminas\Session\SaveHandler\Cache
        return true;
    }
}
