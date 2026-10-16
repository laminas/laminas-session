<?php

declare(strict_types=1);

namespace Laminas\Session\Config;

use Laminas\Session\Exception;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use SessionHandlerInterface;

use function array_search;
use function array_shift;
use function assert;
use function class_exists;
use function explode;
use function implode;
use function in_array;
use function ini_get;
use function ini_set;
use function is_a;
use function is_array;
use function is_string;
use function ob_get_clean;
use function ob_start;
use function preg_match;
use function preg_split;
use function restore_error_handler;
use function session_set_save_handler;
use function session_start;
use function session_status;
use function session_write_close;
use function set_error_handler;
use function sprintf;
use function str_contains;
use function strtolower;
use function trigger_error;
use function trim;

use const E_USER_DEPRECATED;
use const E_WARNING;
use const INFO_MODULES;
use const PHP_SESSION_ACTIVE;
use const PHP_VERSION_ID;

/**
 * Session configuration proxying to session INI options
 */
class SessionConfig extends StandardConfig
{
    /**
     * @internal
     *
     * @var callable
     */
    public static $phpinfo = 'phpinfo';

    /**
     * @internal
     *
     * @var callable
     */
    public static $sessionModuleName = 'session_module_name';

    /**
     * List of known PHP save handlers.
     *
     * @var null|array
     */
    protected ?array $knownSaveHandlers = null;

    /**
     * Used with {@link handleError()}; stores PHP error code
     */
    protected ?int $phpErrorCode = null;

    /**
     * Used with {@link handleError()}; stores PHP error message
     */
    protected ?string $phpErrorMessage = null;

    /** Default number of seconds to make session sticky, when rememberMe() is called */
    protected ?int $rememberMeSeconds = 1_209_600; // 2 weeks

    /**
     * Name of the save handler currently in use. This will either be a PHP
     * built-in save handler name, or the name of a SessionHandlerInterface
     * class being used as a save handler.
     */
    protected null|string|SaveHandlerInterface $saveHandler = null;

    /** session.serialize_handler */
    protected string $serializeHandler;

    /** Valid cache limiters (per session.cache_limiter) */
    protected array $validCacheLimiters = [
        '',
        'nocache',
        'public',
        'private',
        'private_no_expire',
    ];

    /** Valid sid bits per character (per session.sid_bits_per_character) */
    protected array $validSidBitsPerCharacters = [
        4,
        5,
        6,
    ];

    /**
     * Override standard option setting.
     *
     * Provides an overload for setting the save handler.
     *
     * {@inheritDoc}
     */
    public function setOption(string $option, mixed $value): ConfigInterface
    {
        switch (strtolower($option)) {
            case 'save_handler':
                $this->setPhpSaveHandler($value);
                return $this;
            default:
                return parent::setOption($option, $value);
        }
    }

    /**
     * Set storage option in backend configuration store
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setStorageOption(string $storageName, mixed $storageValue): SessionConfig
    {
        switch ($storageName) {
            case 'url_rewriter_tags':
                $key = 'url_rewriter.tags';
                break;
            case 'remember_me_seconds':
                // do nothing; not an INI option
            case 'save_handler':
                // Save handlers must be treated differently due to changes
                // introduced in PHP 7.2. Do not alter running INI setting.
                return $this;
            default:
                $key = 'session.' . $storageName;
                break;
        }

        $iniGet       = ini_get($key);
        $storageValue = (string) $storageValue;
        if (false !== $iniGet && $iniGet === $storageValue) {
            return $this;
        }

        $sessionRequiresRestart = false;
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
            $sessionRequiresRestart = true;
        }

        $result = ini_set($key, $storageValue);

        if ($sessionRequiresRestart) {
            session_start();
        }

        if (false === $result) {
            throw new Exception\InvalidArgumentException(
                "'{$key}' is not a valid sessions-related ini setting."
            );
        }
        return $this;
    }

    /**
     * Retrieve a storage option from a backend configuration store
     *
     * Used to retrieve default values from a backend configuration store.
     */
    public function getStorageOption(string $storageOption): mixed
    {
        return match ($storageOption) {
             // No remote storage option; just return the current value
            'remember_me_seconds' => $this->rememberMeSeconds,

            'url_rewriter_tags' => ini_get('url_rewriter.tags'),

            // The following all need a transformation on the retrieved value;
            // however they use the same key naming scheme
            'use_cookies',
            'use_only_cookies',
            'use_trans_sid',
            'cookie_httponly' => (bool) ini_get('session.' . $storageOption),

            'save_handler' => $this->saveHandler ?? $this->sessionModuleName(),

            default => ini_get('session.' . $storageOption),
        };
    }

    /**
     * Proxy to setPhpSaveHandler()
     *
     * Prevents calls to `setSaveHandler()` from hitting `setOption()` instead,
     * and thus bypassing the logic of `setPhpSaveHandler()`.
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setSaveHandler(string|SessionHandlerInterface $phpSaveHandler): SessionConfig
    {
        return $this->setPhpSaveHandler($phpSaveHandler);
    }

    /**
     * Set session.save_handler
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setPhpSaveHandler(string|SessionHandlerInterface $phpSaveHandler): SessionConfig
    {
        $this->saveHandler             = $this->performSaveHandlerUpdate($phpSaveHandler);
        $this->options['save_handler'] = $this->saveHandler;
        return $this;
    }

    /**
     * Set session.save_path
     *
     * @throws Exception\InvalidArgumentException On invalid path.
     */
    public function setSavePath(string $savePath): SessionConfig
    {
        if ($this->getOption('save_handler') === 'files') {
            parent::setSavePath($savePath);
        }
        $this->savePath = $savePath;
        $this->setOption('save_path', $savePath);
        return $this;
    }

    /**
     * Set session.serialize_handler
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setSerializeHandler(string $serializeHandler): SessionConfig
    {
        set_error_handler([$this, 'handleError']);
        ini_set('session.serialize_handler', $serializeHandler);
        restore_error_handler();
        if ($this->phpErrorCode >= E_WARNING) {
            throw new Exception\InvalidArgumentException('Invalid serialize handler specified');
        }

        $this->serializeHandler = $serializeHandler;
        return $this;
    }

    // session.cache_limiter

    /**
     * Set cache limiter
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setCacheLimiter(string $cacheLimiter): SessionConfig
    {
        if (! in_array($cacheLimiter, $this->validCacheLimiters)) {
            throw new Exception\InvalidArgumentException('Invalid cache limiter provided');
        }
        $this->setOption('cache_limiter', $cacheLimiter);
        ini_set('session.cache_limiter', $cacheLimiter);
        return $this;
    }

    /**
     * Set session.sid_bits_per_character
     *
     * @deprecated see https://wiki.php.net/rfc/deprecations_php_8_4#sessionsid_length_and_sessionsid_bits_per_character
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setSidBitsPerCharacter(int $sidBitsPerCharacter): SessionConfig
    {
        if (PHP_VERSION_ID >= 80400) {
            trigger_error('session.sid_bits_per_character is deprecated starting with PHP 8.4', E_USER_DEPRECATED);
        }

        if (! in_array($sidBitsPerCharacter, $this->validSidBitsPerCharacters)) {
            throw new Exception\InvalidArgumentException('Invalid sid bits per character provided');
        }

        $this->setOption('sid_bits_per_character', $sidBitsPerCharacter);
        ini_set('session.sid_bits_per_character', $sidBitsPerCharacter);
        return $this;
    }

    /**
     * Handle PHP errors
     */
    protected function handleError(int $code, string $message): void
    {
        $this->phpErrorCode    = $code;
        $this->phpErrorMessage = $message;
    }

    /**
     * Determine what save handlers are available.
     *
     * The only way to get at this information is via phpinfo(), and the output
     * of that function varies based on the SAPI.
     *
     * Strips the handler "user" from the list, as PHP 7.2 does not allow
     * setting that as a handler, because it essentially requires you to have
     * already set a custom handler via `session_set_save_handler()`. It
     * wasn't really valid in prior versions, either; the language simply did
     * not complain previously.
     */
    private function locateRegisteredSaveHandlers(): array
    {
        if (null !== $this->knownSaveHandlers) {
            return $this->knownSaveHandlers;
        }

        if (! preg_match('#Registered save handlers.*#m', $this->getPhpInfoForModules(), $matches)) {
            $this->knownSaveHandlers = [];
            return $this->knownSaveHandlers;
        }

        $content = array_shift($matches);
        assert(is_string($content));

        $handlers = str_contains($content, '</td>')
            ? $this->parseSaveHandlersFromHtml($content)
            : $this->parseSaveHandlersFromPlainText($content);

        if (false !== ($index = array_search('user', $handlers, true))) {
            unset($handlers[$index]);
        }

        $this->knownSaveHandlers = $handlers;

        return $this->knownSaveHandlers;
    }

    /**
     * Perform a session.save_handler update.
     *
     * Determines if the save handler represents a PHP built-in
     * save handler, and, if so, passes that value to session_module_name
     * in order to activate it. The save handler name is then returned.
     *
     * If it is not, it tests to see if it is a SessionHandlerInterface
     * implementation. If the string is a class implementing that interface,
     * it creates an instance of it. In such cases, it then calls
     * session_set_save_handler to activate it. The class name of the
     * handler is returned.
     *
     * In all other cases, an exception is raised.
     *
     * @throws Exception\InvalidArgumentException If an error occurs when
     *     setting a PHP session save handler module.
     * @throws Exception\InvalidArgumentException If the $phpSaveHandler
     *     is a string that does not represent a class implementing
     *     SessionHandlerInterface.
     * @throws Exception\InvalidArgumentException If $phpSaveHandler is
     *     a non-string value that does not implement SessionHandlerInterface.
     */
    private function performSaveHandlerUpdate(string|SessionHandlerInterface $phpSaveHandler): string
    {
        if (is_string($phpSaveHandler)) {
            $knownHandlers = $this->locateRegisteredSaveHandlers();

            if (in_array($phpSaveHandler, $knownHandlers, true)) {
                $phpSaveHandler = strtolower($phpSaveHandler);
                set_error_handler([$this, 'handleError']);
                $this->sessionModuleName($phpSaveHandler);
                restore_error_handler();
                if ($this->phpErrorCode >= E_WARNING) {
                    assert(is_string($this->phpErrorMessage));
                    throw new Exception\InvalidArgumentException(sprintf(
                        'Error setting session save handler module "%s": %s',
                        $phpSaveHandler,
                        $this->phpErrorMessage
                    ));
                }

                return $phpSaveHandler;
            }

            if (
                ! class_exists($phpSaveHandler)
                || ! is_a($phpSaveHandler, SessionHandlerInterface::class, true)
            ) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Invalid save handler specified ("%s"); must be one of [%s]'
                    . ' or a class implementing %s',
                    $phpSaveHandler,
                    implode(', ', $knownHandlers),
                    SessionHandlerInterface::class
                ));
            }

            $phpSaveHandler = new $phpSaveHandler();
        }

        if (! $phpSaveHandler instanceof SessionHandlerInterface) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid save handler specified ("%s"); must implement %s',
                $phpSaveHandler::class,
                SessionHandlerInterface::class
            ));
        }

        session_set_save_handler($phpSaveHandler);

        return $phpSaveHandler::class;
    }

    /**
     * Grab module information from phpinfo.
     *
     * Requires capturing an output buffer, as phpinfo does not have an option
     * to return the value as a string.
     */
    private function getPhpInfoForModules(): string
    {
        $phpinfo = self::$phpinfo;

        ob_start();
        $phpinfo(INFO_MODULES);
        $ret = ob_get_clean();
        assert(is_string($ret));

        return $ret;
    }

    /**
     * Parse a list of PHP session save handlers from HTML.
     *
     * Format is "<tr><td class="e">Registered save handlers</td><td class="v">{handlers}</td></tr>".
     */
    private function parseSaveHandlersFromHtml(string $content): array
    {
        if (! preg_match('#<td class="v">(?P<handlers>[^<]+)</td>#', $content, $matches)) {
            return [];
        }

        $handlers = trim($matches['handlers']);
        $ret      = preg_split('#\s+#', $handlers);
        assert(is_array($ret));
        return $ret;
    }

    /**
     * Parse a list of PHP session save handlers from plain text.
     *
     * Format is "Registered save handlers => <handlers>".
     */
    private function parseSaveHandlersFromPlainText(string $content): array
    {
        [$prefix, $handlers] = explode('=>', $content);
        $handlers            = trim($handlers);
        $ret                 = preg_split('#\s+#', $handlers);
        assert(is_array($ret));
        return $ret;
    }

    private function sessionModuleName(?string $module = null): string|false
    {
        $callback = self::$sessionModuleName;
        // session_module_name behaves differently when passed an explicit
        // `null` than it does when passed no arguments.
        if (null !== $module) {
            return $callback($module);
        }

        return $callback();
    }
}
