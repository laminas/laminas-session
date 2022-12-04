<?php

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
     * @param  mixed $key
     * @return mixed
     */
    public function &__get($key)
    {
        return $_SESSION[$key];
    }

    /**
     * Offset Get
     *
     * @param  mixed $key
     * @return mixed
     */
    public function &offsetGet($key)
    {
        return $_SESSION[$key];
    }
}
