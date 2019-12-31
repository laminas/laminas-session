<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Session\Storage;

if (version_compare(PHP_VERSION, '5.3.4', 'lt')) {
    class_alias('Laminas\Session\Storage\AbstractSessionArrayStorage', 'Laminas\Session\Storage\AbstractBaseSessionArrayStorage');
} else {
    class_alias('Laminas\Session\Storage\SessionArrayStorage\PhpReferenceCompatibility', 'Laminas\Session\Storage\AbstractBaseSessionArrayStorage');
}

/**
 * Session storage in $_SESSION
 */
class SessionArrayStorage extends AbstractBaseSessionArrayStorage
{
}
