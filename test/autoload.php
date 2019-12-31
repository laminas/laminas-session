<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */

if (! class_exists(PHPUnit\Framework\Error\Deprecated::class)) {
    class_alias(PHPUnit_Framework_Error_Deprecated::class, PHPUnit\Framework\Error\Deprecated::class);
}
