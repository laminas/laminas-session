<?php

declare(strict_types=1);

namespace Laminas\Session;

use function assert;
use function is_string;

/**
 * Session storage container
 *
 * Allows for interacting with session storage in isolated containers, which
 * may have their own expiries, or even expiries per key in the container.
 * Additionally, expiries may be absolute TTLs or measured in "hops", which
 * are based on how many times the key or container were accessed.
 *
 * @template TKey of string
 * @template TValue
 * @template-extends AbstractContainer<TKey, TValue>
 */
class Container extends AbstractContainer
{
    /**
     * Retrieve a specific key in the container
     *
     * @param TKey|non-empty-string $key
     */
    public function &offsetGet(mixed $key): mixed
    {
        assert(is_string($key) && $key !== '');
        $ret = null;
        if (! $this->offsetExists($key)) {
            return $ret;
        }
        $storage = $this->getStorage();
        $name    = $this->getName();
        $ret     = &$storage[$name][$key];

        return $ret;
    }
}
