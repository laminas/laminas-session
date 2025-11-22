<?php

declare(strict_types=1);

namespace Laminas\Session\Validator;

use Laminas\Session\Exception\SessionValidationFailedException;

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
     */
    public function __construct(array $options = [])
    {
    }

    /**
     * Is the current session identifier valid?
     *
     * Tests that the identifier does not contain invalid characters.
     *
     * @throws SessionValidationFailedException
     */
    public function validate(EnvironmentInterface $initial, EnvironmentInterface $current): void
    {
        $sessionId = $current->getSessionId();

        if ($sessionId === null) {
            throw new SessionValidationFailedException('Session id validation failed');
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

        if (! (bool) preg_match($pattern, $sessionId)) {
            throw new SessionValidationFailedException('Session id validation failed');
        }
    }
}
