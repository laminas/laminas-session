<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Session;

if (version_compare(PHP_VERSION, '5.3.4', 'lt')) {
    class_alias('Laminas\Session\AbstractContainer', 'Laminas\Session\AbstractBaseContainer');
} else {
    class_alias('Laminas\Session\Container\PhpReferenceCompatibility', 'Laminas\Session\AbstractBaseContainer');
}

/**
 * Session storage container
 *
 * Allows for interacting with session storage in isolated containers, which
 * may have their own expiries, or even expiries per key in the container.
 * Additionally, expiries may be absolute TTLs or measured in "hops", which
 * are based on how many times the key or container were accessed.
 */
class Container extends AbstractBaseContainer
{
}
