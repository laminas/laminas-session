<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-validator for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Zend\Session\Validator;

use Zend\EventManager\EventManager;

/**
 * Validator chain for validating sessions (for use with zend-eventmanager v2)
 */
class ValidatorChainEM2 extends EventManager
{
    use ValidatorChainTrait;

    /**
     * Attach a listener to the session validator chain.
     *
     * @param string $event
     * @param null|callable $callback
     * @param int $priority
     * @return \Zend\Stdlib\CallbackHandler
     */
    public function attach($event, $callback = null, $priority = 1)
    {
        return $this->attachValidator($event, $callback, $priority);
    }
}
