<?php

/**
 * @see       https://github.com/laminas/laminas-session for the canonical source repository
 * @copyright https://github.com/laminas/laminas-session/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-session/blob/master/LICENSE.md New BSD License
 */
namespace Laminas\Session\Validator;

use Laminas\EventManager\EventManager;

/**
 * Validator chain for validating sessions (for use with laminas-eventmanager v3)
 */
class ValidatorChainEM3 extends EventManager
{
    use ValidatorChainTrait;

    /**
     * Attach a listener to the session validator chain.
     *
     * @param string $eventName
     * @param callable $callback
     * @param int $priority
     * @return \Laminas\Stdlib\CallbackHandler
     */
    public function attach($eventName, callable $callback, $priority = 1)
    {
        return $this->attachValidator($eventName, $callback, $priority);
    }
}
