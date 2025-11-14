<?php

declare(strict_types=1);

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Config\ConfigInterface;

final class TestConfig implements ConfigInterface
{
    public function setOptions(array $options): self
    {
        return $this;
    }

    public function getOptions(): array
    {
        return [];
    }

    public function setOption(string $option, mixed $value): self
    {
        return $this;
    }

    public function getOption(string $option): mixed
    {
        return false;
    }

    public function hasOption(string $option): bool
    {
        return false;
    }

    public function toArray(): array
    {
        return [];
    }

    public function setName(string $name): ConfigInterface
    {
        return $this;
    }

    public function getName(): string
    {
        return '';
    }

    public function setSavePath(string $savePath): self
    {
        return $this;
    }

    public function getSavePath(): string
    {
        return '';
    }

    public function setCookieLifetime(int $cookieLifetime): self
    {
        return $this;
    }

    public function getCookieLifetime(): int
    {
        return 0;
    }

    public function setCookiePath(string $cookiePath): self
    {
        return $this;
    }

    public function getCookiePath(): string
    {
        return '';
    }

    public function setCookieDomain(string $cookieDomain): self
    {
        return $this;
    }

    public function getCookieDomain(): string
    {
        return '';
    }

    public function setCookieSecure(bool $cookieSecure): self
    {
        return $this;
    }

    public function getCookieSecure(): bool
    {
        return true;
    }

    public function setCookieHttpOnly(bool $cookieHttpOnly): self
    {
        return $this;
    }

    public function getCookieHttpOnly(): bool
    {
        return false;
    }

    public function setUseCookies(bool $useCookies): self
    {
        return $this;
    }

    public function getUseCookies(): bool
    {
        return false;
    }

    public function setRememberMeSeconds(int $rememberMeSeconds): self
    {
        return $this;
    }

    public function getRememberMeSeconds(): int
    {
        return 0;
    }
}
