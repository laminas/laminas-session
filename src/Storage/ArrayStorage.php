<?php

declare(strict_types=1);

namespace Laminas\Session\Storage;

use AllowDynamicProperties;
use ArrayIterator;
use ArrayObject;
use Laminas\Session\Exception;

use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_replace_recursive;
use function assert;
use function is_array;
use function is_string;
use function microtime;
use function sprintf;

/**
 * Array session storage
 *
 * Defines an ArrayObject interface for accessing session storage, with options
 * for setting metadata, locking, and marking as isImmutable.
 *
 * @template TKey of string
 * @template TValue
 * @template-extends ArrayObject<TKey, TValue>
 * @template-implements StorageInterface<TKey, TValue>
 */
#[AllowDynamicProperties]
class ArrayStorage extends ArrayObject implements StorageInterface
{
    /**
     * Is storage marked isImmutable?
     */
    protected bool $isImmutable = false;

    /**
     * Constructor
     *
     * Instantiates storage as an ArrayObject, allowing property access.
     * Also sets the initial request access time.
     *
     * @param array<TKey, TValue>|StorageInterface $input
     */
    public function __construct(
        array|StorageInterface $input = [],
        int $flags = ArrayObject::ARRAY_AS_PROPS,
        string $iteratorClass = ArrayIterator::class
    ) {
        parent::__construct($input, $flags, $iteratorClass);
        $this->setRequestAccessTime(microtime(true));
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
     * Retrieve the request access time
     */
    public function getRequestAccessTime(): float
    {
        return (float) $this->getMetadata('_REQUEST_ACCESS_TIME');
    }

    /**
     * Get Offset
     *
     * @param TKey|non-empty-string $key
     */
    public function __get(string $key): mixed
    {
        assert($key !== '');
        /** @psalm-var TKey $key */
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
        assert($key !== '');
        $this->offsetSet($key, $value);
    }

    /**
     * Set a value in the storage object
     *
     * If the object is marked as isImmutable, or the object or key is marked as
     * locked, raises an exception.
     *
     * @param  TKey|non-empty-string $offset
     * @throws Exception\RuntimeException
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert(is_string($offset) && $offset !== '');
        if ($this->isImmutable()) {
            throw new Exception\RuntimeException(
                sprintf('Cannot set key "%s" as storage is marked isImmutable', $offset)
            );
        }
        if ($this->isLocked($offset)) {
            throw new Exception\RuntimeException(
                sprintf('Cannot set key "%s" due to locking', $offset)
            );
        }

        /** @psalm-var TKey $offset */
        parent::offsetSet($offset, $value);
    }

    /**
     * Lock this storage instance, or a key within it
     *
     * @param TKey|non-empty-string|null $key
     */
    public function lock(?string $key = null): static
    {
        if (null === $key) {
            $this->setMetadata('_READONLY', true);

            return $this;
        }

        /** @psalm-var TKey $key */
        if (isset($this[$key])) {
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
        } elseif ($readOnly && $locks) {
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
     * Mark the storage container as isImmutable
     */
    public function markImmutable(): static
    {
        $this->isImmutable = true;

        return $this;
    }

    /**
     * Is the storage container marked as isImmutable?
     */
    public function isImmutable(): bool
    {
        return $this->isImmutable;
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
     * @param  TKey|non-empty-string $key
     * @throws Exception\RuntimeException
     */
    public function setMetadata(string $key, mixed $value, bool $overwriteArray = false): static
    {
        if ($this->isImmutable) {
            throw new Exception\RuntimeException(
                sprintf('Cannot set key "%s" as storage is marked isImmutable', $key)
            );
        }

        if (! isset($this['__Laminas'])) {
            $this['__Laminas'] = [];
        }

        if (isset($this['__Laminas'][$key]) && is_array($value)) {
            if ($overwriteArray) {
                $this['__Laminas'][$key] = $value;
            } else {
                $this['__Laminas'][$key] = array_replace_recursive($this['__Laminas'][$key], $value);
            }
        } else {
            if ((null === $value) && isset($this['__Laminas'][$key])) {
                // unset($this['__Laminas'][$key]) led to "indirect modification...
                // has no effect" errors, so explicitly pulling array and
                // unsetting key.
                $array = $this['__Laminas'];
                unset($array[$key]);
                $this['__Laminas'] = $array;
                unset($array);
            } elseif (null !== $value) {
                $this['__Laminas'][$key] = $value;
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
        if (! isset($this['__Laminas'])) {
            return false;
        }

        if (null === $key) {
            return $this['__Laminas'];
        }

        if (! array_key_exists($key, $this['__Laminas'])) {
            return false;
        }

        return $this['__Laminas'][$key];
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

        /** @psalm-var TKey $key */
        if (! isset($this[$key])) {
            return $this;
        }

        // Clear key data
        unset($this[$key]);

        // Clear key metadata
        $this->setMetadata($key, null)
            ->unlock($key);

        return $this;
    }

    /**
     * Load the storage from another array
     *
     * Overwrites any data that was previously set.
     */
    public function fromArray(array $array): static
    {
        $ts = $this->getRequestAccessTime();
        $this->exchangeArray($array);
        $this->setRequestAccessTime($ts);

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
        $values = $this->getArrayCopy();

        if ($metadata) {
            return $values;
        }
        if (isset($values['__Laminas'])) {
            unset($values['__Laminas']);
        }

        return $values;
    }
}
