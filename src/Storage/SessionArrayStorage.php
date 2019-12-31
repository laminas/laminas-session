<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Session\Storage;

/**
 * Session storage in $_SESSION
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
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }

    /**
     * Offset Get
     *
     * @param  mixed $key
     * @return mixed
     */
    public function &offsetGet($key)
    {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }
}
