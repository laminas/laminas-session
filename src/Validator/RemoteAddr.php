<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Validator\ValidatorInterface as SessionValidator;

use function array_diff;
use function array_map;
use function array_pop;
use function explode;
use function in_array;

/**
 * @psalm-type OptionsArgument = array{
 * use_proxy?: bool,
 * trusted_proxies?: array<string>,
 * proxy_header?: non-empty-string,
 * }
 */
final class RemoteAddr implements SessionValidator
{
    public ?string $initialData = null;
    public ?string $currentData = null;

    /**
     * Constructor
     * get the current user IP and store it in the session as 'valid data'
     *
     * @param OptionsArgument $options
     */
    public function __construct(
        public readonly Environment $initial,
        public readonly Environment $current,
        array $options = []
    ) {
        if (isset($options['use_proxy']) && $options['use_proxy'] === true) {
            $this->initialData = $this->getIpAddress($this->initial, $options);
            $this->currentData = $this->getIpAddress($this->current, $options);
        } else {
            $this->initialData = $this->initial->remoteAddr;
            $this->currentData = $this->current->remoteAddr;
        }
    }

    /**
     * isValid() - this method will determine if the current user IP matches the
     * IP we stored when we initialized this variable.
     */
    public function isValid(): bool
    {
        return $this->initialData === $this->currentData;
    }

    /**
     * Returns client IP address.
     *
     * @param OptionsArgument $options
     */
    public static function getIpAddress(Environment $initial, array $options = []): ?string
    {
        $ip = self::getIpAddressFromProxy($initial, $options);

        if ($ip !== false) {
            return $ip;
        }

        if ($initial->remoteAddr !== null) {
            return $initial->remoteAddr;
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
    private static function getIpAddressFromProxy(Environment $initial, array $options = []): string|false
    {
        $trustedProxies = $options['trusted_proxies'] ?? [];

        if (
            ! (isset($options['use_proxy']) && $options['use_proxy'])
            || ($initial->remoteAddr !== null && ! in_array($initial->remoteAddr, $trustedProxies))
        ) {
            return false;
        }

        $proxyHeader = $initial->forwardedFor;

        if ($proxyHeader === null || $proxyHeader === '') {
            return false;
        }

        // Extract IPs
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
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
