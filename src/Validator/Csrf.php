<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Container;
use Laminas\Validator\AbstractValidator;

use function assert;
use function explode;
use function is_array;
use function is_string;
use function md5;
use function random_bytes;
use function sprintf;
use function str_replace;
use function strtr;

/**
 * @psalm-type OptionsArgument = array{
 *     name?: non-empty-string,
 *     salt?: non-empty-string,
 *     session?: Container,
 *     timeout?: ?int,
 * }
 * @final
 */
final class Csrf extends AbstractValidator
{
    /**
     * Error codes
     *
     * @const string
     */
    public const NOT_SAME = 'notSame';

    /**
     * Error messages
     *
     * @var array<string, string>
     */
    protected array $messageTemplates = [
        self::NOT_SAME => 'The form submitted did not originate from the expected site',
    ];

    /**
     * Actual hash used.
     */
    private ?string $hash = null;

    /**
     * Name of CSRF element (used to create non-colliding hashes)
     *
     * @var non-empty-string
     */
    private string $name = 'csrf';

    /**
     * Salt for CSRF token
     *
     * @var non-empty-string
     */
    private string $salt = 'salt';

    private ?Container $session = null;

    /**
     * TTL for CSRF token
     */
    private int|null $timeout = 300;

    /** @param OptionsArgument $options */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
    }

    /**
     * Does the provided token match the one generated?
     *
     * @param array<string, mixed>|null $context
     */
    public function isValid(mixed $value, array|null $context = null): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $this->setValue($value);

        $tokenId = $this->getTokenIdFromHash($value);
        $hash    = $this->getValidationToken($tokenId);

        $tokenFromValue = $this->getTokenFromHash($value);
        $tokenFromHash  = $this->getTokenFromHash($hash);

        if ($tokenFromValue === null || $tokenFromHash === null || ($tokenFromValue !== $tokenFromHash)) {
            $this->error(self::NOT_SAME);
            return false;
        }

        return true;
    }

    /**
     * Set CSRF name
     *
     * @deprecated This method will be removed in version 3.0
     *
     * @param non-empty-string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get CSRF name
     *
     * @deprecated This method will be removed in version 3.0
     *
     * @return non-empty-string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set session container
     *
     * @deprecated This method will be removed in version 3.0
     */
    public function setSession(Container $session): void
    {
        $this->session = $session;
        if ($this->hash !== null) {
            $this->initCsrfToken();
        }
    }

    /**
     * Get session container
     *
     * Instantiate session container if none currently exists
     *
     * @deprecated This method will be removed in version 3.0
     */
    public function getSession(): Container
    {
        if (null === $this->session) {
            $this->session = new Container($this->getSessionName());
        }
        return $this->session;
    }

    /**
     * Salt for CSRF token
     *
     * @deprecated This method will be removed in version 3.0
     *
     * @param non-empty-string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * Retrieve salt for CSRF token
     *
     * @deprecated This method will be removed in version 3.0
     *
     * @return non-empty-string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * Retrieve CSRF token
     *
     * If no CSRF token currently exists, or should be regenerated,
     * generates one.
     *
     * @deprecated This method will be removed in version 3.0
     */
    public function getHash(bool $regenerate = false): string
    {
        if ((null === $this->hash) || $regenerate) {
            $this->generateHash();
        }

        assert($this->hash !== null);

        return $this->hash;
    }

    /**
     * Get session namespace for CSRF token
     *
     * Generates a session namespace based on salt, element name, and class.
     */
    public function getSessionName(): string
    {
        return str_replace('\\', '_', self::class) . '_'
            . $this->getSalt() . '_'
            . strtr($this->getName(), ['[' => '_', ']' => '']);
    }

    /**
     * Set timeout for CSRF session token
     *
     * @deprecated This method will be removed in version 3.0
     */
    public function setTimeout(int|null $ttl): void
    {
        $this->timeout = $ttl;
    }

    /**
     * Get CSRF session token timeout
     *
     * @deprecated This method will be removed in version 3.0
     */
    public function getTimeout(): int|null
    {
        return $this->timeout;
    }

    /**
     * Initialize CSRF token in session
     */
    private function initCsrfToken(): void
    {
        $session = $this->getSession();
        $timeout = $this->getTimeout();
        if (null !== $timeout) {
            $session->setExpirationSeconds($timeout);
        }

        $hash    = $this->getHash();
        $token   = $this->getTokenFromHash($hash);
        $tokenId = $this->getTokenIdFromHash($hash);
        assert(is_string($tokenId));

        $tokenList = $session->tokenList ?? [];
        assert(is_array($tokenList));
        $tokenList[$tokenId] = $token;

        $session->tokenList = $tokenList;
        $session->hash      = $hash; // @todo remove this, left for BC
    }

    /**
     * Generate CSRF token
     *
     * Generates CSRF token and stores both in {@link $hash} and element value.
     */
    private function generateHash(): void
    {
        $token = md5($this->getSalt() . random_bytes(32) . $this->getName());

        $this->hash = $this->formatHash($token, $this->generateTokenId());

        $this->setValue($this->hash);
        $this->initCsrfToken();
    }

    private function generateTokenId(): string
    {
        return md5(random_bytes(32));
    }

    /**
     * Get validation token
     *
     * Retrieve token from session, if it exists.
     */
    private function getValidationToken(string|null $tokenId = null): string|null
    {
        $session = $this->getSession();

        /**
         * if no tokenId is passed we revert to the old behaviour
         *
         * @todo remove, here for BC
         */
        if ($tokenId === null && isset($session->hash) && is_string($session->hash)) {
            return $session->hash;
        }

        if ($tokenId !== null && isset($session->tokenList[$tokenId]) && is_string($session->tokenList[$tokenId])) {
            return $this->formatHash($session->tokenList[$tokenId], $tokenId);
        }

        return null;
    }

    private function formatHash(string $token, string $tokenId): string
    {
        return sprintf('%s-%s', $token, $tokenId);
    }

    private function getTokenFromHash(?string $hash): ?string
    {
        if (null === $hash) {
            return null;
        }

        $data = explode('-', $hash);
        return $data[0] ?: null;
    }

    private function getTokenIdFromHash(string $hash): ?string
    {
        $data = explode('-', $hash);

        if (! isset($data[1])) {
            return null;
        }

        return $data[1];
    }
}
