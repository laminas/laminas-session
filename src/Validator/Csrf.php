<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Container;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;

use function assert;
use function explode;
use function is_array;
use function is_int;
use function is_string;
use function md5;
use function random_bytes;
use function sprintf;
use function str_replace;
use function strtr;

/**
 * @psalm-type OptionsArgument = array{
 * hash?: non-empty-string,
 * name?: non-empty-string,
 * salt?: non-empty-string,
 * session?: Container,
 * timeout?: int,
 * messages?: array<string, string>,
 * translator?: TranslatorInterface|null,
 * translatorTextDomain?: string|null,
 * translatorEnabled?: bool,
 * valueObscured?: bool,
 * ...<string, mixed>
 * }
 */
final class Csrf extends AbstractValidator
{
    /**
     * Error codes
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
    private string $hash;

    /**
     * Name of CSRF element (used to create non-colliding hashes)
     *
     * @var non-empty-string
     */
    private string $name;

    /**
     * Salt for CSRF token
     *
     * @var non-empty-string
     */
    private string $salt;

    private Container $session;

    /**
     * TTL for CSRF token
     */
    private ?int $timeout;

    /** @param OptionsArgument $options  */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        $hash    = $options['hash'] ?? null;
        $name    = $options['name'] ?? 'csrf';
        $salt    = $options['salt'] ?? 'salt';
        $session = $options['session'] ?? null;
        $timeout = $options['timeout'] ?? 300;

        assert(is_string($hash) || $hash === null);
        assert(is_string($name) && $name !== '');
        assert(is_string($salt) && $salt !== '');
        assert($session instanceof Container || $session === null);
        assert(is_int($timeout) || $timeout === null);

        $this->name    = $name;
        $this->salt    = $salt;
        $this->timeout = $timeout;
        $this->session = $this->getSessionContainer($session);

        if (null === $hash) {
            $this->generateHash();
        } else {
            $this->hash = $hash;
        }
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

    private function getSessionContainer(?Container $container): Container
    {
        if (null === $container) {
            $container = new Container($this->getSessionName());
        }
        return $container;
    }

    /**
     * Get session namespace for CSRF token
     *
     * Generates a session namespace based on salt, element name, and class.
     */
    public function getSessionName(): string
    {
        return str_replace('\\', '_', self::class) . '_'
            . $this->salt . '_'
            . strtr($this->name, ['[' => '_', ']' => '']);
    }

    /**
     * Initialize CSRF token in session
     */
    private function initCsrfToken(): void
    {
        $session = $this->session;
        $timeout = $this->timeout;
        if (null !== $timeout) {
            $session->setExpirationSeconds($timeout);
        }

        $hash    = $this->hash;
        $token   = $this->getTokenFromHash($hash);
        $tokenId = $this->getTokenIdFromHash($hash);
        assert(is_string($tokenId));

        $tokenList = $session->tokenList ?? [];
        assert(is_array($tokenList));
        $tokenList[$tokenId] = $token;

        $session->tokenList = $tokenList;
    }

    /**
     * Generate CSRF token
     *
     * Generates CSRF token and stores both in {@link $hash} and element value.
     */
    private function generateHash(): void
    {
        $token      = md5($this->salt . random_bytes(32) . $this->name);
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
        $session = $this->session;

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
