<?php

declare(strict_types=1);

namespace Laminas\Session\Storage;

use ArrayAccess;
use Countable;
use Serializable;
use Traversable;

/**
 * Session storage interface
 *
 * Defines the minimum requirements for handling userland, in-script session
 * storage (e.g., the $_SESSION superglobal array).
 *
 * @template TKey of array-key
 * @template TValue
 * @template-extends Traversable<TKey, TValue>
 * @template-extends ArrayAccess<TKey, TValue>
 */
interface StorageInterface extends Traversable, ArrayAccess, Serializable, Countable
{
    public function getRequestAccessTime(): float;

    public function lock(null|int|string $key = null): StorageInterface;

    public function isLocked(null|int|string $key = null): bool;

    public function unlock(null|int|string $key = null): StorageInterface;

    public function markImmutable(): StorageInterface;

    public function isImmutable(): bool;

    public function setMetadata(string $key, mixed $value, bool $overwriteArray = false): StorageInterface;

    public function getMetadata(null|int|string $key = null): mixed;

    public function clear(null|int|string $key = null): StorageInterface;

    public function fromArray(array $array): StorageInterface;

    public function toArray(bool $metadata = false): array;
}
