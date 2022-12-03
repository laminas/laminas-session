<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Storage\StorageInterface;

/**
 * @template-covariant TKey of array-key
 * @template-covariant TValue
 * @template-extends StorageInterface<TKey, TValue>
 */
interface Php81CompatibleStorageInterface extends StorageInterface
{
    public function __serialize(): array;

    public function __unserialize(array $session): void;
}
