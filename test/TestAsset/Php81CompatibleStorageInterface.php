<?php
declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;
use Laminas\Session\Storage\StorageInterface;

interface Php81CompatibleStorageInterface extends StorageInterface
{
    public function __serialize(): array;

    public function __unserialize(array $session): void;
}
