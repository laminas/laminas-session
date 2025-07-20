<?php

declare(strict_types=1);

namespace Laminas\Session\Storage;

use ArrayIterator;
use IteratorAggregate;
use Laminas\Session\Exception;
use Traversable;

use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_replace_recursive;
use function assert;
use function count;
use function is_array;
use function is_string;
use function iterator_to_array;
use function microtime;
use function serialize;
use function sprintf;
use function unserialize;

/**
 * Session storage in $_SESSION
 *
 * Replaces the $_SESSION superglobal with an ArrayObject that allows for
 * property access, metadata storage, locking, and immutability.
 *
 * @template TKey of string
 * @template TValue
 * @template-implements IteratorAggregate<TKey, TValue>
 * @template-implements StorageInterface<TKey, TValue>
 */
abstract class AbstractSessionArrayStorage implements
    IteratorAggregate,
    StorageInterface,
    StorageInitializationInterface
{
    /**
     * @param iterable<string, mixed>|null $input
     */
    public function __construct(?iterable $input = null)
    {
        // this is here for B.C.
        $this->init($input);
    }

    /**
     * Initialize Storage
     */
    public function init(?iterable $input = null): void
    {
        $input  ??= $_SESSION ?? [];
        $input    = $input instanceof Traversable ? iterator_to_array($input) : $input;
        $_SESSION = $input;

        $this->setRequestAccessTime(microtime(true));
    }

    /**
     * Get Offset
     *
     * @param TKey|non-empty-string $key
     */
    public function __get(string $key): mixed
    {
        return $this->offsetGet($key);
    }

    /**
     * Set Offset
     *
     * @param TKey|non-empty-string $key
     * @param TValue $value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Isset Offset
     *
     * @param TKey|non-empty-string $key
     */
    public function __isset(string $key): bool
    {
        return $this->offsetExists($key);
    }

    /**
     * Unset Offset
     *
     * @param TKey|non-empty-string $key
     */
    public function __unset(string $key): void
    {
        assert($key !== '');
        $this->offsetUnset($key);
    }

    /**
     * Destructor
     *
     * @return void
     */
    public function __destruct()
    {
    }

    /**
     * Offset Exists
     *
     * @param TKey|non-empty-string $key
     */
    public function offsetExists(mixed $key): bool
    {
        assert(is_string($key) && $key !== '');
        return isset($_SESSION[$key]);
    }

    /**
     * Offset Get
     *
     * @param TKey|non-empty-string $key
     */
    public function offsetGet(mixed $key): mixed
    {
        assert(is_string($key) && $key !== '');
        return $_SESSION[$key] ?? null;
    }

    /**
     * Offset Set
     *
     * @param TKey|non-empty-string $offset
     * @param TValue $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert(is_string($offset) && $offset !== '');
        $_SESSION[$offset] = $value;
    }

    /**
     * Offset Unset
     *
     * @param TKey|non-empty-string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        assert(is_string($offset) && $offset !== '');
        unset($_SESSION[$offset]);
    }

    /**
     * Count
     */
    public function count(): int
    {
        return count($_SESSION);
    }

    /**
     * Serialize
     */
    public function serialize(): string
    {
        return serialize($_SESSION);
    }

    /**
     * Unserialize
     *
     * @param non-empty-string $session
     */
    public function unserialize(string $session): mixed
    {
        return unserialize($session);
    }

    /**
     * Retrieve an external iterator
     *
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        /** @var Traversable<TKey, TValue> $return */
        $return = new ArrayIterator($_SESSION);
        return $return;
    }

    /**
     * Load session object from an existing array
     *
     * Ensures $_SESSION is set to an instance of the object when complete.
     *
     * @template TKeyIn of string
     * @template TValIn
     * @param    array<TKeyIn, TValIn> $array
     * @return   static<TKeyIn, TValIn>
     */
    public function fromArray(array $array): static
    {
        $ts       = $this->getRequestAccessTime();
        $_SESSION = $array;
        $this->setRequestAccessTime($ts);

        return $this;
    }

    /**
     * Mark object as isImmutable
     */
    public function markImmutable(): static
    {
        $_SESSION['_IMMUTABLE'] = true;

        return $this;
    }

    /**
     * Determine if this object is isImmutable
     */
    public function isImmutable(): bool
    {
        return isset($_SESSION['_IMMUTABLE']) && $_SESSION['_IMMUTABLE'];
    }

    /**
     * Lock this storage instance, or a key within it
     *
     * @psalm-param TKey|non-empty-string|null $key
     */
    public function lock(?string $key = null): static
    {
        if (null === $key) {
            $this->setMetadata('_READONLY', true);

            return $this;
        }
        if (isset($_SESSION[$key])) {
            $this->setMetadata('_LOCKS', [$key => true]);
        }

        return $this;
    }

    /**
     * Is the object or key marked as locked?
     *
     * @param TKey|non-empty-string|null $key
     */
    public function isLocked(?string $key = null): bool
    {
        if ($this->isImmutable()) {
            // isImmutable trumps all
            return true;
        }

        if (null === $key) {
            // testing for global lock
            return $this->getMetadata('_READONLY');
        }

        $locks    = $this->getMetadata('_LOCKS');
        $readOnly = $this->getMetadata('_READONLY');

        if ($readOnly && ! $locks) {
            // global lock in play; all keys are locked
            return true;
        }
        if ($readOnly && $locks) {
            return array_key_exists($key, $locks);
        }

        // test for individual locks
        if (! $locks) {
            return false;
        }

        return array_key_exists($key, $locks);
    }

    /**
     * Unlock an object or key marked as locked
     *
     * @param TKey|non-empty-string|null $key
     */
    public function unlock(?string $key = null): static
    {
        if (null === $key) {
            // Unlock everything
            $this->setMetadata('_READONLY', false);
            $this->setMetadata('_LOCKS', false);

            return $this;
        }

        $locks = $this->getMetadata('_LOCKS');
        if (! $locks) {
            if (! $this->getMetadata('_READONLY')) {
                return $this;
            }
            $array = $this->toArray();
            $keys  = array_keys($array);
            $locks = array_flip($keys);
            unset($array, $keys);
        }

        if (array_key_exists($key, $locks)) {
            unset($locks[$key]);
            $this->setMetadata('_LOCKS', $locks, true);
        }

        return $this;
    }

    /**
     * Set storage metadata
     *
     * Metadata is used to store information about the data being stored in the
     * object. Some example use cases include:
     * - Setting expiry data
     * - Maintaining access counts
     * - localizing session storage
     * - etc.
     *
     * $overwriteArray Whether to overwrite or merge array values; by default, merges
     *
     * @param  TKey|non-empty-string $key
     * @throws Exception\RuntimeException
     */
    public function setMetadata(string $key, mixed $value, bool $overwriteArray = false): static
    {
        if ($this->isImmutable()) {
            throw new Exception\RuntimeException(
                sprintf('Cannot set key "%s" as storage is marked isImmutable', $key)
            );
        }

        if (! isset($_SESSION['__Laminas']) || ! is_array($_SESSION['__Laminas'])) {
            $_SESSION['__Laminas'] = [];
        }
        if (isset($_SESSION['__Laminas'][$key]) && is_array($value)) {
            if ($overwriteArray) {
                $_SESSION['__Laminas'][$key] = $value;
            } else {
                $_SESSION['__Laminas'][$key] = array_replace_recursive($_SESSION['__Laminas'][$key], $value);
            }
        } else {
            if ((null === $value) && isset($_SESSION['__Laminas'][$key])) {
                $array = $_SESSION['__Laminas'];
                unset($array[$key]);
                $_SESSION['__Laminas'] = $array;
                unset($array);
            } elseif (null !== $value) {
                $_SESSION['__Laminas'][$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Retrieve metadata for the storage object or a specific metadata key
     *
     * Returns false if no metadata stored, or no metadata exists for the given
     * key.
     *
     * @param TKey|non-empty-string|null $key
     */
    public function getMetadata(?string $key = null): mixed
    {
        if (! isset($_SESSION['__Laminas'])) {
            return false;
        }

        if (null === $key) {
            return $_SESSION['__Laminas'];
        }

        if (! array_key_exists($key, $_SESSION['__Laminas'])) {
            return false;
        }

        return $_SESSION['__Laminas'][$key];
    }

    /**
     * Clear the storage object or a subkey of the object
     *
     * @param  TKey|non-empty-string|null $key
     * @throws Exception\RuntimeException
     */
    public function clear(?string $key = null): static
    {
        if ($this->isImmutable()) {
            throw new Exception\RuntimeException('Cannot clear storage as it is marked immutable');
        }
        if (null === $key) {
            $this->fromArray([]);

            return $this;
        }

        unset($_SESSION[$key]);
        $this->setMetadata($key, null)
            ->unlock($key);

        return $this;
    }

    /**
     * Retrieve the request access time
     */
    public function getRequestAccessTime(): float
    {
        return (float) $this->getMetadata('_REQUEST_ACCESS_TIME');
    }

    /**
     * Set the request access time
     */
    protected function setRequestAccessTime(float $time): static
    {
        $this->setMetadata('_REQUEST_ACCESS_TIME', $time);

        return $this;
    }

    /**
     * Cast the object to an array
     *
     * @param  bool $metadata Whether to include metadata
     * @return array<TKey, TValue>
     */
    public function toArray(bool $metadata = false): array
    {
        /** @var iterable $values */
        $values = $_SESSION ?? [];

        /** @var array<TKey, TValue> $values */
        $values = is_array($values) ? $values : iterator_to_array($values);

        if ($metadata) {
            return $values;
        }

        if (isset($values['__Laminas'])) {
            unset($values['__Laminas']);
        }

        return $values;
    }

    public function __serialize(): array
    {
        return $_SESSION;
    }

    public function __unserialize(array $session)
    {
    }
}
