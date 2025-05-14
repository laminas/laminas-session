<?php

declare(strict_types=1);

namespace Laminas\Session\Config;

use Laminas\Session\Exception;
use Laminas\Validator\Hostname as HostnameValidator;

use function array_key_exists;
use function array_merge;
use function array_shift;
use function implode;
use function is_dir;
use function is_writable;
use function method_exists;
use function parse_url;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strtolower;
use function substr;
use function trigger_error;
use function ucwords;

use const E_USER_DEPRECATED;
use const PHP_URL_PATH;
use const PHP_VERSION_ID;

/**
 * Standard session configuration
 */
class StandardConfig implements ConfigInterface, SameSiteCookieCapableInterface
{
    /**
     * session.name
     */
    protected ?string $name = null;

    /**
     * session.save_path
     */
    protected ?string $savePath = null;

    /**
     * session.cookie_lifetime
     */
    protected ?int $cookieLifetime = null;

    /**
     * session.cookie_path
     */
    protected ?string $cookiePath = null;

    /**
     * session.cookie_domain
     */
    protected ?string $cookieDomain = null;

    /**
     * session.cookie_samesite
     */
    protected ?string $cookieSameSite = null;

    /**
     * session.cookie_secure
     */
    protected ?bool $cookieSecure = null;

    /**
     * session.cookie_httponly
     */
    protected ?bool $cookieHttpOnly = null;

    /**
     * remember_me_seconds
     */
    protected ?int $rememberMeSeconds = null;

    /**
     * session.use_cookies
     */
    protected ?bool $useCookies = null;

    /**
     * All options
     *
     * @var array<string, mixed>
     */
    protected array $options = [];

    /**
     * Set many options at once
     *
     * If a setter method exists for the key, that method will be called;
     * otherwise, a standard option will be set with the value provided via
     * {@link setOption()}.
     *
     * @param array<string, mixed> $options
     */
    public function setOptions(iterable $options): StandardConfig
    {
        foreach ($options as $key => $value) {
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            } else {
                $this->setOption($key, $value);
            }
        }
        return $this;
    }

    /**
     * Get all options set
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set an individual option
     *
     * Keys are normalized to lowercase. After setting internally, calls
     * {@link setStorageOption()} to allow further processing.
     */
    public function setOption(string $option, mixed $value): ConfigInterface
    {
        $option                 = strtolower($option);
        $this->options[$option] = $value;
        $this->setStorageOption($option, $value);
        return $this;
    }

    /**
     * Get an individual option
     *
     * Keys are normalized to lowercase. If the option is not found, attempts
     * to retrieve it via {@link getStorageOption()}; if a value is returned
     * from that method, it will be set as the internal value and returned.
     *
     * Returns null for unfound options
     */
    public function getOption(string $option): mixed
    {
        $option = strtolower($option);
        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        $value = $this->getStorageOption($option);
        if (null !== $value) {
            $this->setOption($option, $value);
            return $value;
        }

        return null;
    }

    /**
     * Check to see if an internal option has been set for the key provided.
     */
    public function hasOption(string $option): bool
    {
        $option = strtolower($option);
        return array_key_exists($option, $this->options);
    }

    /**
     * Set storage option in backend configuration store
     *
     * Does nothing in this implementation; others might use it to set things
     * such as INI settings.
     */
    public function setStorageOption(string $storageName, mixed $storageValue): StandardConfig
    {
        return $this;
    }

    /**
     * Retrieve a storage option from a backend configuration store
     *
     * Used to retrieve default values from a backend configuration store.
     */
    public function getStorageOption(string $storageOption): mixed
    {
    }

    /**
     * Set session.save_path
     *
     * @throws Exception\InvalidArgumentException On invalid path.
     */
    public function setSavePath(string $savePath): StandardConfig
    {
        if (! is_dir($savePath)) {
            throw new Exception\InvalidArgumentException('Invalid save_path provided; not a directory');
        }
        if (! is_writable($savePath)) {
            throw new Exception\InvalidArgumentException('Invalid save_path provided; not writable');
        }

        $this->savePath = $savePath;
        $this->setStorageOption('save_path', $savePath);
        return $this;
    }

    /**
     * Set session.save_path
     */
    public function getSavePath(): string
    {
        if (null === $this->savePath) {
            $this->savePath = $this->getStorageOption('save_path');
        }
        return $this->savePath;
    }

    /**
     * Set session.name
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setName(string $name): StandardConfig
    {
        if ($name === '') {
            throw new Exception\InvalidArgumentException('Invalid session name; cannot be empty');
        }
        $this->name = $name;

        $this->setStorageOption('name', $this->name);
        return $this;
    }

    /**
     * Get session.name
     */
    public function getName(): string
    {
        if (null === $this->name) {
            $this->name = $this->getStorageOption('name');
        }
        return $this->name;
    }

    /**
     * Set session.gc_probability
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setGcProbability(int $gcProbability): StandardConfig
    {
        if (0 > $gcProbability || 100 < $gcProbability) {
            throw new Exception\InvalidArgumentException('Invalid gc_probability; must be a percentage');
        }
        $this->setOption('gc_probability', $gcProbability);
        $this->setStorageOption('gc_probability', $gcProbability);
        return $this;
    }

    /**
     * Get session.gc_probability
     */
    public function getGcProbability(): int
    {
        if (! isset($this->options['gc_probability'])) {
            $this->options['gc_probability'] = $this->getStorageOption('gc_probability');
        }

        return (int) $this->options['gc_probability'];
    }

    /**
     * Set session.gc_divisor
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setGcDivisor(int $gcDivisor): StandardConfig
    {
        if (1 > $gcDivisor) {
            throw new Exception\InvalidArgumentException('Invalid gc_divisor; must be a positive integer');
        }
        $this->setOption('gc_divisor', $gcDivisor);
        $this->setStorageOption('gc_divisor', $gcDivisor);
        return $this;
    }

    /**
     * Get session.gc_divisor
     */
    public function getGcDivisor(): int
    {
        if (! isset($this->options['gc_divisor'])) {
            $this->options['gc_divisor'] = $this->getStorageOption('gc_divisor');
        }

        return (int) $this->options['gc_divisor'];
    }

    /**
     * Set gc_maxlifetime
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setGcMaxlifetime(int $gcMaxlifetime): StandardConfig
    {
        if (1 > $gcMaxlifetime) {
            throw new Exception\InvalidArgumentException('Invalid gc_maxlifetime; must be a positive integer');
        }

        $this->setOption('gc_maxlifetime', $gcMaxlifetime);
        $this->setStorageOption('gc_maxlifetime', $gcMaxlifetime);
        return $this;
    }

    /**
     * Get session.gc_maxlifetime
     */
    public function getGcMaxlifetime(): int
    {
        if (! isset($this->options['gc_maxlifetime'])) {
            $this->options['gc_maxlifetime'] = $this->getStorageOption('gc_maxlifetime');
        }

        return (int) $this->options['gc_maxlifetime'];
    }

    /**
     * Set session.cookie_lifetime
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setCookieLifetime(int $cookieLifetime): StandardConfig
    {
        if (0 > $cookieLifetime) {
            throw new Exception\InvalidArgumentException(
                'Invalid cookie_lifetime; must be a positive integer or zero'
            );
        }

        $this->cookieLifetime = $cookieLifetime;
        $this->setStorageOption('cookie_lifetime', $cookieLifetime);
        return $this;
    }

    /**
     * Get session.cookie_lifetime
     */
    public function getCookieLifetime(): int
    {
        if (null === $this->cookieLifetime) {
            $this->cookieLifetime = (int) $this->getStorageOption('cookie_lifetime');
        }
        return $this->cookieLifetime;
    }

    /**
     * Set session.cookie_path
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setCookiePath(string $cookiePath): StandardConfig
    {
        $test = parse_url($cookiePath, PHP_URL_PATH);
        if ($test !== $cookiePath || '/' !== $test[0]) {
            throw new Exception\InvalidArgumentException('Invalid cookie path');
        }

        $this->cookiePath = $cookiePath;
        $this->setStorageOption('cookie_path', $cookiePath);
        return $this;
    }

    /**
     * Get session.cookie_path
     */
    public function getCookiePath(): string
    {
        if (null === $this->cookiePath) {
            $this->cookiePath = (string) $this->getStorageOption('cookie_path');
        }
        return $this->cookiePath;
    }

    /**
     * Set session.cookie_domain
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setCookieDomain(string $cookieDomain): StandardConfig
    {
        $validator = new HostnameValidator(['allow' => HostnameValidator::ALLOW_ALL]);

        if (! empty($cookieDomain) && ! $validator->isValid($cookieDomain)) {
            throw new Exception\InvalidArgumentException(
                'Invalid cookie domain: ' . implode('; ', $validator->getMessages())
            );
        }

        $this->cookieDomain = $cookieDomain;
        $this->setStorageOption('cookie_domain', $cookieDomain);
        return $this;
    }

    /**
     * Get session.cookie_domain
     */
    public function getCookieDomain(): string
    {
        if (null === $this->cookieDomain) {
            $this->cookieDomain = (string) $this->getStorageOption('cookie_domain');
        }
        return $this->cookieDomain;
    }

    /**
     * Set session.cookie_samesite
     */
    public function setCookieSameSite(string $cookieSameSite): StandardConfig
    {
        $this->cookieSameSite = $cookieSameSite;
        $this->setStorageOption('cookie_samesite', $this->cookieSameSite);
        return $this;
    }

    /**
     * Get session.cookie_samesite
     */
    public function getCookieSameSite(): string
    {
        if (null === $this->cookieSameSite) {
            $this->cookieSameSite = (string) $this->getStorageOption('cookie_samesite');
        }
        return $this->cookieSameSite;
    }

    /**
     * Set session.cookie_secure
     */
    public function setCookieSecure(bool $cookieSecure): StandardConfig
    {
        $this->cookieSecure = $cookieSecure;
        $this->setStorageOption('cookie_secure', $this->cookieSecure);
        return $this;
    }

    /**
     * Get session.cookie_secure
     */
    public function getCookieSecure(): bool
    {
        if (null === $this->cookieSecure) {
            $this->cookieSecure = (bool) $this->getStorageOption('cookie_secure');
        }
        return $this->cookieSecure;
    }

    /**
     * Set session.cookie_httponly
     *
     * case sensitive method lookups in setOptions means this method has an
     * unusual casing
     */
    public function setCookieHttpOnly(bool $cookieHttpOnly): StandardConfig
    {
        $this->cookieHttpOnly = $cookieHttpOnly;
        $this->setStorageOption('cookie_httponly', $this->cookieHttpOnly);
        return $this;
    }

    /**
     * Get session.cookie_httponly
     */
    public function getCookieHttpOnly(): bool
    {
        if (null === $this->cookieHttpOnly) {
            $this->cookieHttpOnly = (bool) $this->getStorageOption('cookie_httponly');
        }
        return $this->cookieHttpOnly;
    }

    /**
     * Set session.use_cookies
     */
    public function setUseCookies(bool $useCookies): StandardConfig
    {
        $this->useCookies = $useCookies;
        $this->setStorageOption('use_cookies', $this->useCookies);
        return $this;
    }

    /**
     * Get session.use_cookies
     */
    public function getUseCookies(): bool
    {
        if (null === $this->useCookies) {
            $this->useCookies = (bool) $this->getStorageOption('use_cookies');
        }
        return $this->useCookies;
    }

    /**
     * Set session.cache_expire
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setCacheExpire(int $cacheExpire): StandardConfig
    {
        if (1 > $cacheExpire) {
            throw new Exception\InvalidArgumentException('Invalid cache_expire; must be a positive integer');
        }

        $this->setOption('cache_expire', $cacheExpire);
        $this->setStorageOption('cache_expire', $cacheExpire);
        return $this;
    }

    /**
     * Get session.cache_expire
     */
    public function getCacheExpire(): int
    {
        if (! isset($this->options['cache_expire'])) {
            $this->options['cache_expire'] = $this->getStorageOption('cache_expire');
        }

        return (int) $this->options['cache_expire'];
    }

    /**
     * Set session.sid_length
     *
     * @deprecated see https://wiki.php.net/rfc/deprecations_php_8_4#sessionsid_length_and_sessionsid_bits_per_character
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setSidLength(int $sidLength): StandardConfig
    {
        if (PHP_VERSION_ID >= 80400) {
            trigger_error('session.sid_length is deprecated starting with PHP 8.4', E_USER_DEPRECATED);
        }

        if ($sidLength < 22 || $sidLength > 256) {
            throw new Exception\InvalidArgumentException('Invalid length provided');
        }

        $this->setOption('sid_length', $sidLength);
        $this->setStorageOption('sid_length', $sidLength);
        return $this;
    }

    /**
     * Get session.sid_length
     */
    public function getSidLength(): int
    {
        if (! isset($this->options['sid_length'])) {
            $this->options['sid_length'] = $this->getStorageOption('sid_length');
        }

        return (int) $this->options['sid_length'];
    }

    /**
     * Set session.sid_bits_per_character
     */
    public function setSidBitsPerCharacter(int $sidBitsPerCharacter): StandardConfig
    {
        if (PHP_VERSION_ID >= 80400) {
            trigger_error('session.sid_bits_per_character is deprecated starting with PHP 8.4', E_USER_DEPRECATED);
        }

        $this->setOption('sid_bits_per_character', $sidBitsPerCharacter);
        $this->setStorageOption('sid_bits_per_character', $sidBitsPerCharacter);
        return $this;
    }

    /**
     * Get session.sid_bits_per_character
     */
    public function getSidBitsPerCharacter(): int
    {
        if (! isset($this->options['sid_bits_per_character'])) {
            $this->options['sid_bits_per_character'] = $this->getStorageOption('sid_bits_per_character');
        }

        return (int) $this->options['sid_bits_per_character'];
    }

    /**
     * Set remember_me_seconds
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setRememberMeSeconds(int $rememberMeSeconds): StandardConfig
    {
        if (1 > $rememberMeSeconds) {
            throw new Exception\InvalidArgumentException('Invalid remember_me_seconds; must be a positive integer');
        }

        $this->rememberMeSeconds = $rememberMeSeconds;
        $this->setStorageOption('remember_me_seconds', $rememberMeSeconds);
        return $this;
    }

    /**
     * Get remember_me_seconds
     */
    public function getRememberMeSeconds(): int
    {
        if (null === $this->rememberMeSeconds) {
            $this->rememberMeSeconds = (int) $this->getStorageOption('remember_me_seconds');
        }
        return $this->rememberMeSeconds;
    }

    /**
     * Cast configuration to an array
     */
    public function toArray(): array
    {
        $extraOpts = [
            'cookie_domain'       => $this->getCookieDomain(),
            'cookie_httponly'     => $this->getCookieHttpOnly(),
            'cookie_lifetime'     => $this->getCookieLifetime(),
            'cookie_path'         => $this->getCookiePath(),
            'cookie_samesite'     => $this->getCookieSameSite(),
            'cookie_secure'       => $this->getCookieSecure(),
            'name'                => $this->getName(),
            'remember_me_seconds' => $this->getRememberMeSeconds(),
            'save_path'           => $this->getSavePath(),
            'use_cookies'         => $this->getUseCookies(),
        ];
        return array_merge($this->options, $extraOpts);
    }

    /**
     * Intercept get*() and set*() methods
     *
     * Intercepts getters and setters and passes them to getOption() and setOption(),
     * respectively.
     *
     * @throws Exception\BadMethodCallException On non-getter/setter method.
     */
    public function __call(string $method, array $args): mixed
    {
        $prefix = substr($method, 0, 3);
        $option = substr($method, 3);
        $key    = strtolower(preg_replace('#(?<=[a-z])([A-Z])#', '_\1', $option));

        if ($prefix === 'set') {
            $value = array_shift($args);
            return $this->setOption($key, $value);
        } elseif ($prefix === 'get') {
            return $this->getOption($key);
        } else {
            throw new Exception\BadMethodCallException(sprintf(
                'Method "%s" does not exist in %s',
                $method,
                static::class
            ));
        }
    }
}
