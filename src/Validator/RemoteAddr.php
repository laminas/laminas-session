<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Validator\ValidatorInterface as SessionValidator;

/**
 * @implements SessionValidator<string>
 */
use function array_diff;
use function array_map;
use function array_pop;
use function assert;
use function explode;
use function in_array;
use function is_string;
use function str_replace;
use function strpos;
use function strtoupper;

class RemoteAddr implements SessionValidator
{
    /**
     * Internal data.
     *
     * @var string
     */
    protected $data;

    /**
     * Whether to use proxy addresses or not.
     *
     * As default this setting is disabled - IP address is mostly needed to increase
     * security. HTTP_* are not reliable since can easily be spoofed. It can be enabled
     * just for more flexibility, but if user uses proxy to connect to trusted services
     * it's his/her own risk, only reliable field for IP address is $_SERVER['REMOTE_ADDR'].
     *
     * @var bool
     */
    protected static $useProxy = false;

    /**
     * List of trusted proxy IP addresses
     *
     * @var array
     */
    protected static $trustedProxies = [];

    /**
     * HTTP header to introspect for proxies
     *
     * @var string
     */
    protected static $proxyHeader = 'HTTP_X_FORWARDED_FOR';

    /**
     * Constructor
     * get the current user IP and store it in the session as 'valid data'
     *
     * @param null|string $data
     */
    public function __construct($data = null)
    {
        if ($data === null || $data === '') {
            $data = $this->getIpAddress();
        }

        $this->data = $data;
    }

    /**
     * isValid() - this method will determine if the current user IP matches the
     * IP we stored when we initialized this variable.
     */
    public function isValid(): bool
    {
        return $this->getIpAddress() === $this->getData();
    }

    /**
     * Changes proxy handling setting.
     *
     * This must be static method, since validators are recovered automatically
     * at session read, so this is the only way to switch setting.
     *
     * @param bool  $useProxy Whether to check also proxied IP addresses.
     * @return void
     */
    public static function setUseProxy($useProxy = true)
    {
        static::$useProxy = $useProxy;
    }

    /**
     * Checks proxy handling setting.
     *
     * @return bool Current setting value.
     */
    public static function getUseProxy()
    {
        return static::$useProxy;
    }

    /**
     * Set list of trusted proxy addresses
     *
     * @return void
     */
    public static function setTrustedProxies(array $trustedProxies)
    {
        static::$trustedProxies = $trustedProxies;
    }

    /**
     * Set the header to introspect for proxy IPs
     */
    public static function setProxyHeader(string $header = 'X-Forwarded-For'): void
    {
        static::$proxyHeader = self::normalizeProxyHeader($header);
    }

    /**
     * Returns client IP address.
     */
    protected function getIpAddress(): string
    {
        $this->setUseProxy(static::$useProxy);
        $this->setTrustedProxies(static::$trustedProxies);
        $this->setProxyHeader(static::$proxyHeader);

        return $this->getClientIpAddress();
    }

    /**
     * Returns client IP address.
     */
    private function getClientIpAddress(): string
    {
        $ip = $this->getIpAddressFromProxy();

        if (false !== $ip) {
            return $ip;
        }

        // direct IP address
        if (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '';
    }

    /**
     * Attempt to get the IP address for a proxied client
     *
     * @see http://tools.ietf.org/html/draft-ietf-appsawg-http-forwarded-10#section-5.2
     */
    private function getIpAddressFromProxy(): string|false
    {
        if (
            ! static::$useProxy
            || (isset($_SERVER['REMOTE_ADDR']) && ! in_array($_SERVER['REMOTE_ADDR'], static::$trustedProxies))
        ) {
            return false;
        }

        $header = static::$proxyHeader;
        if (! isset($_SERVER[$header]) || '' === $_SERVER[$header]) {
            return false;
        }

        // Extract IPs
        assert(is_string($_SERVER[$header]));
        $ips = explode(',', $_SERVER[$header]);
        // trim, so we can compare against trusted proxies properly
        $ips = array_map('trim', $ips);
        // remove trusted proxy IPs
        $ips = array_diff($ips, static::$trustedProxies);

        // Any left?
        if (empty($ips)) {
            return false;
        }

        // Since we've removed any known, trusted proxy servers, the right-most
        // address represents the first IP we do not know about -- i.e., we do
        // not know if it is a proxy server, or a client. As such, we treat it
        // as the originating IP.
        // @see http://en.wikipedia.org/wiki/X-Forwarded-For
        return array_pop($ips);
    }

    /**
     * Normalize a header string
     *
     * Normalizes a header string to a format that is compatible with
     * $_SERVER
     *
     * @param  string $header
     * @return string
     */
    protected static function normalizeProxyHeader($header)
    {
        $header = strtoupper($header);
        $header = str_replace('-', '_', $header);
        if (0 !== strpos($header, 'HTTP_')) {
            $header = 'HTTP_' . $header;
        }
        return $header;
    }

    /**
     * Retrieve token for validating call
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
