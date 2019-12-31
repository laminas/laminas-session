<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Session\Container;

use Laminas\Session\AbstractContainer;

/**
 * Session storage container for PHP 5.3.4 and above.
 */
abstract class PhpReferenceCompatibility extends AbstractContainer
{
    /**
     * Retrieve a specific key in the container
     *
     * @param  string $key
     * @return mixed
     */
    public function &offsetGet($key)
    {
        $ret = null;
        if (!$this->offsetExists($key)) {
            return $ret;
        }
        $storage = $this->getStorage();
        $name    = $this->getName();
        $ret =& $storage[$name][$key];

        return $ret;
    }
}
