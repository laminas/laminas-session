<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use function ini_get;
use function is_numeric;
use function preg_match;

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
    public function __construct(
        public readonly Environment $initial,
        public readonly Environment $current,
        array $options = []
    ) {
    }

    /**
     * Is the current session identifier valid?
     *
     * Tests that the identifier does not contain invalid characters.
     */
    public function isValid(): bool
    {
        if ($this->current->sessionId === null) {
            return false;
        }

        if (PHP_VERSION_ID >= 80400) {
            // PHP 8.4 deprecated session.sid_bits_per_character and set it hard to "4".
            // Old (pre PHP 8.4) session IDs with a higher bitrate are still valid though.
            $hashBitsPerChar = 6;
        } else {
            // Get the session id bits per character INI setting, using 5 if unavailable
            $hashBitsPerChar = ini_get('session.sid_bits_per_character');
            $hashBitsPerChar = is_numeric($hashBitsPerChar) ? (int) $hashBitsPerChar : 5;
        }

        $pattern = match ($hashBitsPerChar) {
            4 => '#^[0-9a-f]*$#',
            6 => '#^[0-9a-zA-Z-,]*$#',
            // 5
            // intentionally fall-through
            default => '#^[0-9a-v]*$#',
        };

        return (bool) preg_match($pattern, $this->current->sessionId);
    }

    /**
     * Return validator name
     */
    public function getName(): string
    {
        return self::class;
    }
}
