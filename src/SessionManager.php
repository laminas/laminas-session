<?php

declare(strict_types=1);

namespace Laminas\Session;

use Laminas\Session\Exception\SessionValidationFailedException;
use Laminas\Session\Service\EnvironmentFactoryInterface;
use Laminas\Session\Service\GlobalEnvironmentFactory;
use Laminas\Session\Validator\EnvironmentInterface;
use Laminas\Session\Validator\ValidatorInterface;
use Traversable;

use function array_merge;
use function array_unique;
use function assert;
use function headers_sent;
use function is_array;
use function is_string;
use function iterator_to_array;
use function preg_match;
use function register_shutdown_function;
use function serialize;
use function session_destroy;
use function session_id;
use function session_name;
use function session_regenerate_id;
use function session_set_save_handler;
use function session_start;
use function session_status;
use function session_write_close;
use function setcookie;
use function unserialize;

use const PHP_SESSION_ACTIVE;

/**
 * Session ManagerInterface implementation utilizing ext/session
 *
 * @psalm-type OptionsArgument = array{
 *     preserve_storage?: bool,
 *     send_expire_cookie?: bool,
 *     clear_storage?: bool,
 *     attach_default_validators?: bool
 * }
 */
final class SessionManager extends AbstractManager
{
    private bool $preserveStorage;
    private bool $sendExpireCookie;
    private bool $clearStorage;

    /** @var list<ValidatorInterface> */
    private array $defaultValidators = [
        Validator\Id::class,
    ];

    /** value returned by session_name() */
    private string|null $name = null;

    private array $options = [];

    private EnvironmentFactoryInterface $environmentFactory;

    /**
     * Constructor
     *
     * @param list<class-string<ValidatorInterface>> $validators
     * @param OptionsArgument $options
     * @throws Exception\RuntimeException
     */
    public function __construct(
        ?Config\ConfigInterface $config = null,
        ?Storage\StorageInterface $storage = null,
        ?SaveHandler\SaveHandlerInterface $saveHandler = null,
        array $validators = [],
        array $options = [],
        ?EnvironmentFactoryInterface $environmentFactory = null,
    ) {
        $this->preserveStorage    = $options['preserve_storage'] ?? false;
        $this->sendExpireCookie   = $options['send_expire_cookie'] ?? true;
        $this->clearStorage       = $options['clear_storage'] ?? false;
        $this->environmentFactory = $environmentFactory ?? new GlobalEnvironmentFactory();

        if ($options['attach_default_validators'] ?? true) {
            $validators = array_merge($this->defaultValidators, $validators);
        }

        $this->options = $options;

        /** @psalm-var list<ValidatorInterface> $uniqueValidators */
        $uniqueValidators = array_unique($validators);

        parent::__construct($config, $storage, $saveHandler, $uniqueValidators);
        register_shutdown_function($this->writeClose(...));
    }

    /**
     * Does a session exist and is it currently active?
     */
    public function sessionExists(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE || headers_sent();
    }

    /**
     * Start session
     *
     * if No session currently exists, attempt to start it. Calls
     * {@link isValid()} once session_start() is called, and raises an
     * exception if validation fails.
     *
     * @throws Exception\RuntimeException
     */
    public function start(bool|null $preserveStorage = null): void
    {
        if ($this->sessionExists()) {
            return;
        }

        $saveHandler = $this->getSaveHandler();
        if ($saveHandler instanceof SaveHandler\SaveHandlerInterface) {
            // register the session handler with ext/session
            $this->registerSaveHandler($saveHandler);
        }

        $oldSessionData = [];
        if (isset($_SESSION)) {
            $oldSessionData = $_SESSION;

            // convert session data to plain array that’ll be acceptable as
            // array_merge parameter
            if ($oldSessionData instanceof Storage\StorageInterface) {
                $oldSessionData = $oldSessionData->toArray();
            } elseif ($oldSessionData instanceof Traversable) {
                $oldSessionData = iterator_to_array($oldSessionData);
            }
        }

        session_start();

        if (! empty($oldSessionData) && is_array($oldSessionData)) {
            /** @var array<string, mixed> $_SESSION */
            $_SESSION = array_merge($oldSessionData, $_SESSION);
        }

        $storage = $this->getStorage();

        $preserveStorage = $preserveStorage ?? $this->preserveStorage;

        // Since session is starting, we need to potentially repopulate our
        // session storage
        if ($storage instanceof Storage\SessionStorage) {
            if (! $preserveStorage) {
                $storage->fromArray($_SESSION);
            }
            $_SESSION = $storage;
        } elseif ($storage instanceof Storage\StorageInitializationInterface) {
            $storage->init($_SESSION);
        }

        $this->isValid();
    }

    /**
     * Create validators, insert reference value and add them to the validator chain
     *
     * @throws SessionValidationFailedException
     */

    /**
     * Destroy/end a session
     *
     * @param OptionsArgument|null $options
     */
    public function destroy(?array $options = null): void
    {
        // session_destroy() requires active session while method
        // $this->sessionExists() includes other conditions
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $sendExpireCookie = $options['send_expire_cookie'] ?? $this->sendExpireCookie;
        $clearStorage     = $options['clear_storage'] ?? $this->clearStorage;

        session_destroy();
        if (! headers_sent() && $sendExpireCookie) {
            $this->expireSessionCookie();
        }

        if ($clearStorage) {
            $this->getStorage()->clear();
        }
    }

    /**
     * Write session to save handler and close
     *
     * Once done, the Storage object will be marked as isImmutable.
     */
    public function writeClose(): void
    {
        // The assumption is that we're using PHP's ext/session.
        // session_write_close() will actually overwrite $_SESSION with an
        // empty array on completion -- which leads to a mismatch between what
        // is in the storage object and $_SESSION. To get around this, we
        // temporarily reset $_SESSION to an array, and then re-link it to
        // the storage object.
        //
        // Additionally, while you _can_ write to $_SESSION following a
        // session_write_close() operation, no changes made to it will be
        // flushed to the session handler. As such, we now mark the storage
        // object isImmutable.
        $storage = $this->getStorage();
        if (! $storage->isImmutable()) {
            $_SESSION = $storage->toArray(true);
            session_write_close();
            $storage->fromArray($_SESSION);
            $storage->markImmutable();
        }
    }

    /**
     * Attempt to set the session name
     *
     * If the session has already been started, or if the name provided fails
     * validation, an exception will be raised.
     *
     * @throws Exception\InvalidArgumentException
     */
    public function setName(string $name): static
    {
        if ($this->sessionExists()) {
            throw new Exception\InvalidArgumentException(
                'Cannot set session name after a session has already started'
            );
        }

        if (! preg_match('/^[a-zA-Z0-9]+$/', $name)) {
            throw new Exception\InvalidArgumentException(
                'Name provided contains invalid characters; must be alphanumeric only'
            );
        }

        $this->name = $name;
        session_name($name);
        return $this;
    }

    /**
     * Get session name
     *
     * Proxies to {@link session_name()}.
     */
    public function getName(): string
    {
        if (null === $this->name) {
            // If we're grabbing via session_name(), we don't need our
            // validation routine; additionally, calling setName() after
            // session_start() can lead to issues, and often we just need the name
            // in order to do things such as setting cookies.
            $name = session_name();
            assert(is_string($name));

            $this->name = $name;
        }
        return $this->name;
    }

    /**
     * Set session ID
     *
     * Can safely be called in the middle of a session.
     */
    public function setId(string $id): static
    {
        if ($this->sessionExists()) {
            throw new Exception\RuntimeException(
                'Session has already been started, to change the session ID call regenerateId()'
            );
        }
        session_id($id);
        return $this;
    }

    /**
     * Get session ID
     *
     * Proxies to {@link session_id()}
     */
    public function getId(): string
    {
        $ret = session_id();
        assert(is_string($ret));
        return $ret;
    }

    /**
     * Regenerate id
     *
     * Regenerate the session ID, using session save handler's
     * native ID generation Can safely be called in the middle of a session.
     */
    public function regenerateId(bool $deleteOldSession = true): static
    {
        if ($this->sessionExists()) {
            session_regenerate_id($deleteOldSession);
        }

        return $this;
    }

    /**
     * Set the TTL (in seconds) for the session cookie expiry
     *
     * Can safely be called in the middle of a session.
     */
    public function rememberMe(int|null $ttl = null): static
    {
        if (null === $ttl) {
            $ttl = $this->getConfig()->getRememberMeSeconds();
        }
        $this->setSessionCookieLifetime($ttl);
        return $this;
    }

    /**
     * Set a 0s TTL for the session cookie
     *
     * Can safely be called in the middle of a session.
     */
    public function forgetMe(): static
    {
        $this->setSessionCookieLifetime(0);
        return $this;
    }

    /**
     * Is this session valid?
     *
     * Notifies the Validator Chain until either all validators have returned
     * true or one has failed.
     */
    public function isValid(): void
    {
        /** @var array<string, mixed> $storage */
        $storage = $this->getStorage()->getMetadata();

        if (isset($storage['environment'])) {
            assert(is_string($storage['environment']));
            /** @var EnvironmentInterface $initialEnvironment */
            $initialEnvironment = unserialize($storage['environment']);
        } else {
            $initialEnvironment = $this->environmentFactory->getEnvironment();
            $this->getStorage()->setMetadata('environment', serialize($initialEnvironment));
        }

        foreach ($this->validators as $validatorName) {
            $validator = new $validatorName($this->options);

            if (! $validator instanceof ValidatorInterface) {
                continue;
            }

            $validator->validate($initialEnvironment, $this->environmentFactory->getEnvironment());
        }
    }

    /**
     * Expire the session cookie
     *
     * Sends a session cookie with no value, and with an expiry in the past.
     */
    public function expireSessionCookie(): void
    {
        $config = $this->getConfig();
        if (! $config->getUseCookies()) {
            return;
        }
        setcookie(
            $this->getName(), // session name
            '', // value
            $_SERVER['REQUEST_TIME'] - 42000, // TTL for cookie
            $config->getCookiePath(),
            $config->getCookieDomain(),
            $config->getCookieSecure(),
            $config->getCookieHttpOnly()
        );
    }

    /**
     * Set the session cookie lifetime
     *
     * If a session already exists, destroys it (without sending an expiration
     * cookie), regenerates the session ID, and restarts the session.
     */
    private function setSessionCookieLifetime(int $ttl): void
    {
        $config = $this->getConfig();
        if (! $config->getUseCookies()) {
            return;
        }

        // Set new cookie TTL
        $config->setCookieLifetime($ttl);

        if ($this->sessionExists()) {
            // There is a running session so we'll regenerate id to send a new cookie
            $this->regenerateId();
        }
    }

    /**
     * Register Save Handler with ext/session
     *
     * Since ext/session is coupled to this particular session manager
     * register the save handler with ext/session.
     */
    private function registerSaveHandler(SaveHandler\SaveHandlerInterface $saveHandler): bool
    {
        return session_set_save_handler($saveHandler);
    }
}
