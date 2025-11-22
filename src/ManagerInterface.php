<?php

declare(strict_types=1);

namespace Laminas\Session;

use Laminas\Session\Config\ConfigInterface as Config;
use Laminas\Session\Exception\SessionValidationFailedException;
use Laminas\Session\SaveHandler\SaveHandlerInterface as SaveHandler;
use Laminas\Session\Storage\StorageInterface as Storage;

/**
 * Session manager interface
 */
interface ManagerInterface
{
    public function setConfig(Config $config): static;

    public function getConfig(): Config;

    public function setStorage(Storage $storage): static;

    public function getStorage(): Storage;

    public function setSaveHandler(SaveHandler $saveHandler): static;

    public function getSaveHandler(): SaveHandler|null;

    public function sessionExists(): bool;

    public function start(): void;

    public function destroy(): void;

    public function writeClose(): void;

    public function setName(string $name): static;

    public function getName(): string;

    public function setId(string $id): static;

    public function getId(): string;

    public function regenerateId(): static;

    public function rememberMe(int|null $ttl = null): static;

    public function forgetMe(): static;

    public function expireSessionCookie(): void;

    /** @throws SessionValidationFailedException */
    public function isValid(): void;
}
