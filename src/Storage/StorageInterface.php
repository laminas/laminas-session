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
 * @extends ArrayAccess<string, mixed>
 * @template TKey of string
 * @template TValue
 * @template-extends Traversable<TKey, TValue>
 * @template-extends ArrayAccess<TKey, TValue>
 */
interface StorageInterface extends Traversable, ArrayAccess, Serializable, Countable
{
    public function getRequestAccessTime(): float;

    /**
     * @param TKey|non-empty-string|null $key
     */
    public function lock(?string $key = null): static;

    /**
     * @param TKey|non-empty-string|null $key
     */
    public function isLocked(?string $key = null): bool;

    /**
     * @param TKey|non-empty-string|null $key
     */
    public function unlock(?string $key = null): static;

    public function markImmutable(): static;

    public function isImmutable(): bool;

    /**
     * @param TKey|non-empty-string $key
     */
    public function setMetadata(string $key, mixed $value, bool $overwriteArray = false): static;

    /**
     * @param TKey|non-empty-string|null $key
     */
    public function getMetadata(?string $key = null): mixed;

    /**
     * @param TKey|non-empty-string|null $key
     */
    public function clear(?string $key = null): static;

    /**
     * @param array<TKey, TValue> $array
     */
    public function fromArray(array $array): static;

    /**
     * @return array<TKey, TValue>
     */
    public function toArray(bool $metadata = false): array;
}
