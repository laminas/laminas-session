<?php

namespace LaminasTest\Session\TestAsset;

use Laminas\Session\Config\ConfigInterface;

class TestConfig implements ConfigInterface
{
    /**
     * @param array $options
     * @return void
     */
    public function setOptions($options)
    {
    }

    /** @return void */
    public function getOptions()
    {
    }

    /**
     * @param string $option
     * @param mixed $value
     * @return void
     */
    public function setOption($option, $value)
    {
    }

    /**
     * @param string $option
     * @return void
     */
    public function getOption($option)
    {
    }

    /**
     * @param string $option
     * @return void
     */
    public function hasOption($option)
    {
    }

    /** @return void */
    public function toArray()
    {
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
    }

    /** @return void */
    public function getName()
    {
    }

    /**
     * @param string $savePath
     * @return void
     */
    public function setSavePath($savePath)
    {
    }

    /** @return void */
    public function getSavePath()
    {
    }

    /**
     * @param int $cookieLifetime
     * @return void
     */
    public function setCookieLifetime($cookieLifetime)
    {
    }

    /** @return void */
    public function getCookieLifetime()
    {
    }

    /**
     * @param string $cookiePath
     * @return void
     */
    public function setCookiePath($cookiePath)
    {
    }

    /** @return void */
    public function getCookiePath()
    {
    }

    /**
     * @param string $cookieDomain
     * @return void
     */
    public function setCookieDomain($cookieDomain)
    {
    }

    /** @return void */
    public function getCookieDomain()
    {
    }

    /**
     * @param bool $cookieSecure
     * @return void
     */
    public function setCookieSecure($cookieSecure)
    {
    }

    /** @return void */
    public function getCookieSecure()
    {
    }

    /**
     * @param bool $cookieHttpOnly
     * @return void
     */
    public function setCookieHttpOnly($cookieHttpOnly)
    {
    }

    /** @return void */
    public function getCookieHttpOnly()
    {
    }

    /**
     * @param bool $useCookies
     * @return void
     */
    public function setUseCookies($useCookies)
    {
    }

    /** @return void */
    public function getUseCookies()
    {
    }

    /**
     * @param int $rememberMeSeconds
     * @return void
     */
    public function setRememberMeSeconds($rememberMeSeconds)
    {
    }

    /** @return void */
    public function getRememberMeSeconds()
    {
    }
}
