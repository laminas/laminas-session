<?php

declare(strict_types=1);

namespace Laminas\Session\Config;

/**
 * Standard session configuration
 */
interface ConfigInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function setOptions(array $options): ConfigInterface;

    /** @return array<string, mixed> */
    public function getOptions(): array;

    public function setOption(string $option, mixed $value): ConfigInterface;

    public function getOption(string $option): mixed;

    public function hasOption(string $option): bool;

    public function toArray(): array;

    public function setName(string $name): ConfigInterface;

    public function getName(): string;

    public function setSavePath(string $savePath): ConfigInterface;

    public function getSavePath(): string;

    public function setCookieLifetime(int $cookieLifetime): ConfigInterface;

    public function getCookieLifetime(): int;

    public function setCookiePath(string $cookiePath): ConfigInterface;

    public function getCookiePath(): string;

    public function setCookieDomain(string $cookieDomain): ConfigInterface;

    public function getCookieDomain(): string;

    public function setCookieSecure(bool $cookieSecure): ConfigInterface;

    public function getCookieSecure(): bool;

    public function setCookieHttpOnly(bool $cookieHttpOnly): ConfigInterface;

    public function getCookieHttpOnly(): bool;

    public function setUseCookies(bool $useCookies): ConfigInterface;

    public function getUseCookies(): bool;

    public function setRememberMeSeconds(int $rememberMeSeconds): ConfigInterface;

    public function getRememberMeSeconds(): int;
}
