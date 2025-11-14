<?php

declare(strict_types=1);

namespace Laminas\Session\Config;

interface SameSiteCookieCapableInterface
{
    public function setCookieSameSite(string $cookieSameSite): SameSiteCookieCapableInterface;

    public function getCookieSameSite(): string;
}
