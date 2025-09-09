<?php

namespace Laminas\Session\Validator;

use function ini_get;
use function is_numeric;
use function preg_match;
use function session_id;
use function strrpos;
use function substr;

use const PHP_VERSION_ID;

/**
 * session_id validator
 *
 * @final
 */
class Id implements ValidatorInterface
{
    /**
     * Session identifier.
     *
     * @deprecated This property will be removed in version 3.0
     *
     * @var string
     */
    protected $id;

    /**
     * Constructor
     *
     * Allows passing the current session_id; if none provided, uses the PHP
     * session_id() function to retrieve it.
     *
     * @param null|string $id
     */
    public function __construct($id = null)
    {
        if ($id === null || $id === '') {
            $id = session_id();
        }

        $this->id = $id;
    }

    /**
     * Is the current session identifier valid?
     *
     * Tests that the identifier does not contain invalid characters.
     *
     * @return bool
     */
    public function isValid()
    {
        $id          = $this->id;
        $saveHandler = ini_get('session.save_handler');
        if ($saveHandler === 'cluster') { // Zend Server SC, validate only after last dash
            $dashPos = strrpos($id, '-');
            if ($dashPos !== false) {
                $id = substr($id, $dashPos + 1);
            }
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

        return (bool) preg_match($pattern, $id);
    }

    /**
     * Retrieve token for validating call (session_id)
     *
     * @deprecated This method will be removed in version 3.0
     *
     * @return string
     */
    public function getData()
    {
        return $this->id;
    }

    /**
     * Return validator name
     *
     * @return string
     */
    public function getName()
    {
        return self::class;
    }
}
