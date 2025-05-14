<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Session\AbstractManager;
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\ValidatorChain;

final class TestManager extends AbstractManager
{
    public bool $started = false;

    protected string $defaultConfigClass = StandardConfig::class;

    protected string $defaultStorageClass = ArrayStorage::class;

    public function start(): void
    {
        $this->started = true;
    }

    public function destroy(): void
    {
        $this->started = false;
    }

    public function stop(): void
    {
    }

    public function writeClose(): void
    {
        $this->started = false;
    }

    public function getName(): string
    {
        return self::class;
    }

    public function setName(string $name): static
    {
        return $this;
    }

    public function getId(): string
    {
        return 'TestManagerId';
    }

    public function setId(string $id): static
    {
        return $this;
    }

    public function regenerateId(): static
    {
        return $this;
    }

    public function rememberMe(int|null $ttl = null): static
    {
        return $this;
    }

    public function forgetMe(): static
    {
        return $this;
    }

    public function setValidatorChain(EventManagerInterface $chain): static
    {
        return $this;
    }

    public function getValidatorChain(): EventManagerInterface
    {
        return new ValidatorChain($this->getStorage());
    }

    public function isValid(): bool
    {
        return true;
    }

    public function sessionExists(): bool
    {
        return true;
    }

    public function expireSessionCookie(): void
    {
    }
}
