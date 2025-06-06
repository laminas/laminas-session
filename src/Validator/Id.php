<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use function ini_get;
use function is_numeric;
use function preg_match;
use function trigger_error;

use const E_USER_DEPRECATED;
use const PHP_VERSION_ID;

/**
 * session_id validator
 */
final class Id implements ValidatorInterface
{
    /**
     * Constructor
     *
     * Allows passing the current session_id; if none provided, uses the PHP
     * session_id() function to retrieve it.
     */
    public function __construct(public readonly Environment $initial, public readonly Environment $current)
    {
    }

    /**
     * Is the current session identifier valid?
     *
     * Tests that the identifier does not contain invalid characters.
     */
    public function isValid(): bool
    {
        if ($this->initial->sessionId === null) {
            return false;
        }

        if (PHP_VERSION_ID >= 80400) {
            trigger_error('session.sid_bits_per_character is deprecated starting with PHP 8.4', E_USER_DEPRECATED);
        }

        // Get the session id bits per character INI setting, using 5 if unavailable
        $hashBitsPerChar = ini_get('session.sid_bits_per_character');
        $hashBitsPerChar = is_numeric($hashBitsPerChar) ? (int) $hashBitsPerChar : 5;

        $pattern = match ($hashBitsPerChar) {
            4 => '#^[0-9a-f]*$#',
            6 => '#^[0-9a-zA-Z-,]*$#',
            // 5
            // intentionally fall-through
            default => '#^[0-9a-v]*$#',
        };

        return (bool) preg_match($pattern, $this->initial->sessionId);
    }

    /**
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
