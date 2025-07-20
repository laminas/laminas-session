<?php

declare(strict_types=1);

namespace Laminas\Session\Storage;

use function assert;
use function is_string;

/**
 * Session storage in $_SESSION
 *
 * @template TKey of string
 * @template TValue
 * @template-extends AbstractSessionArrayStorage<TKey, TValue>
 */
class SessionArrayStorage extends AbstractSessionArrayStorage
{
    /**
     * Get Offset
     *
     * @param TKey|non-empty-string $key
     */
    public function &__get(mixed $key): mixed
    {
        assert(is_string($key) && $key !== '');
        /** @psalm-var non-empty-string $key */
        return $_SESSION[$key];
    }

    /**
     * Offset Get
     *
     * @param TKey|non-empty-string $key
     */
    public function &offsetGet(mixed $key): mixed
    {
        assert(is_string($key) && $key !== '');
        /** @psalm-var non-empty-string $key */
        return $_SESSION[$key];
    }
}
