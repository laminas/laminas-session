<?php
/**
 * @see       https://github.com/zendframework/zend-session for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-session/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Session\Validator;

use Zend\Session\Storage\StorageInterface;

/**
 * Base trait for validator chain implementations
 */
trait ValidatorChainTrait
{
    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * Retrieve session storage object
     *
     * @return StorageInterface
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Internal implementation for attaching a listener to the
     * session validator chain.
     *
     * @param  string $event
     * @param  callable $callback
     * @param  int $priority
     * @return \Zend\Stdlib\CallbackHandler|callable
     */
    private function attachValidator($event, $callback, $priority)
    {
        $context = null;
        if ($callback instanceof ValidatorInterface) {
            $context = $callback;
        } elseif (is_array($callback)) {
            $test = array_shift($callback);
            if ($test instanceof ValidatorInterface) {
                $context = $test;
            }
            array_unshift($callback, $test);
        }
        if ($context instanceof ValidatorInterface) {
            $data = $context->getData();
            $name = $context->getName();
            $this->getStorage()->setMetadata('_VALID', [$name => $data]);
        }

        $listener = parent::attach($event, $callback, $priority);
        return $listener;
    }
}
