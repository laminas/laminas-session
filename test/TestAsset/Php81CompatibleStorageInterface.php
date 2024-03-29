<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Storage\StorageInterface;

/**
 * @template TKey of array-key
 * @template TValue
 * @template-extends StorageInterface<TKey, TValue>
 */
interface Php81CompatibleStorageInterface extends StorageInterface
{
    public function __serialize(): array;

    public function __unserialize(array $session): void;
}
