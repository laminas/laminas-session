<?php

declare(strict_types=1);

namespace Laminas\Session\Config;

interface SameSiteCookieCapableInterface
{
    /**
     * @param string $cookieSameSite
     * @return self
     */
    public function setCookieSameSite($cookieSameSite);

    /** @return string */
    public function getCookieSameSite();
}
