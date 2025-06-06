<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Validator\ValidatorInterface as SessionValidator;

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
final class RemoteAddr implements SessionValidator
{
    /**
     * Constructor
     * get the current user IP and store it in the session as 'valid data'
     */
    public function __construct(public readonly Environment $initial, public readonly Environment $current)
    {
    }

    /**
     * isValid() - this method will determine if the current user IP matches the
     * IP we stored when we initialized this variable.
     */
    public function isValid(): bool
    {
        return $this->initial->remoteAddr === $this->current->remoteAddr;
    }

    /**
     * Returns client IP address.
     *
     * @param OptionsArgument $options
     */
    public static function getIpAddress(array $options = [], ?string $remoteAddr = null): ?string
    {
        $ip = self::getIpAddressFromProxy($options, $remoteAddr);

        if ($ip !== false) {
            return $ip;
        }

        if ($remoteAddr !== null) {
            return $remoteAddr;
        }

        return null;
    }

    /**
     * Attempt to get the IP address for a proxied client
     *
     * @see http://tools.ietf.org/html/draft-ietf-appsawg-http-forwarded-10#section-5.2
     *
     * @param OptionsArgument $options
     */
    private static function getIpAddressFromProxy(array $options = [], ?string $remoteAddr = null): string|false
    {
        $normalizedProxyHeader = self::normalizeProxyHeader($options['proxy_header'] ?? 'X_FORWARDED_FOR');
        $trustedProxies        = $options['trusted_proxies'] ?? [];

        if (
            ! (isset($options['use_proxy']) && $options['use_proxy'])
            || ($remoteAddr !== null && ! in_array($remoteAddr, $trustedProxies))
        ) {
            return false;
        }

        $proxyHeader = Environment::getServerOption($normalizedProxyHeader);

        if ($proxyHeader === null || $proxyHeader === '') {
            return false;
        }

        // Extract IPs
        assert(is_string($proxyHeader));
        $ips = explode(',', $proxyHeader);
        // trim, so we can compare against trusted proxies properly
        $ips = array_map('trim', $ips);
        // remove trusted proxy IPs
        $ips = array_diff($ips, $trustedProxies);
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
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
