<?php

declare(strict_types=1);

namespace Laminas\Session\Storage;

/**
 * Session storage in $_SESSION'
 *
 * @template TKey of array-key
 * @template TValue
 * @template-extends AbstractSessionArrayStorage<TKey, TValue>
 */
class SessionArrayStorage extends AbstractSessionArrayStorage
{
    /**
     * Get Offset
     *
     * @param non-empty-string $key
     */
    public function &__get(mixed $key): mixed
    {
        return $_SESSION[$key];
    }

    /**
     * Offset Get
     *
     * @param non-empty-string $key
     */
    public function &offsetGet(mixed $key): mixed
    {
        return $_SESSION[$key];
    }
}
