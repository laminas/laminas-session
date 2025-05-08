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

/**
 * @psalm-type OptionsArgument = array{
 * use_proxy?: bool,
 * trusted_proxies?: array<string>,
 * proxy_header?: non-empty-string,
 * }
 */
final class RemoteAddr implements ValidatorInterface
{
    /**
     * Internal data.
     */
    private ?string $data;

    /**
     * Whether to use proxy addresses or not.
     *
     * As default this setting is disabled - IP address is mostly needed to increase
     * security. HTTP_* are not reliable since can easily be spoofed. It can be enabled
     * just for more flexibility, but if user uses proxy to connect to trusted services
     * it's his/her own risk, only reliable field for IP address is $_SERVER['REMOTE_ADDR'].
     */
    private bool $useProxy;

    /**
     * List of trusted proxy IP addresses
     */
    private array $trustedProxies;

    /**
     * HTTP header to introspect for proxies
     */
    private string $proxyHeader;

    /**
     * Constructor
     * get the current user IP and store it in the session as 'valid data'
     *
     * @param OptionsArgument $options
     */
    public function __construct(?string $data = null, array $options = [])
    {
        $proxyHeader = $options['proxy_header'] ?? 'X_FORWARDED_FOR';

        $this->useProxy       = isset($options['use_proxy']) && (bool) $options['use_proxy'];
        $this->trustedProxies = isset($options['trusted_proxies']) ? (array) $options['trusted_proxies'] : [];
        $this->proxyHeader    = self::normalizeProxyHeader($proxyHeader);

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
     * Checks proxy handling setting.
     */
    public function getUseProxy(): bool
    {
        return $this->useProxy;
    }

    /**
     * Returns client IP address.
     */
    private function getIpAddress(): string
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
            ! $this->useProxy
            || (isset($_SERVER['REMOTE_ADDR']) && ! in_array($_SERVER['REMOTE_ADDR'], $this->trustedProxies))
        ) {
            return false;
        }

        $header = $this->proxyHeader;

        if (! isset($_SERVER[$header]) || '' === $_SERVER[$header]) {
            return false;
        }

        // Extract IPs
        assert(is_string($_SERVER[$header]));
        $ips = explode(',', $_SERVER[$header]);
        // trim, so we can compare against trusted proxies properly
        $ips = array_map('trim', $ips);
        // remove trusted proxy IPs
        $ips = array_diff($ips, $this->trustedProxies);
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
     */
    protected static function normalizeProxyHeader(string $header): string
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
    public function getData(): ?string
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
