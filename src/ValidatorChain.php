<?php

namespace Laminas\Session;

use Laminas\EventManager\EventManager;
use Laminas\Session\Storage\StorageInterface;
use Laminas\Session\Validator\ValidatorInterface;

use function array_shift;
use function array_unshift;
use function is_array;

/**
 * @deprecated This class will be removed without replacement in version 3.0.
 *
 * @see https://docs.laminas.dev/laminas-session/v2/migration/preparing-for-v3/
 *
 * The validator list will be built in the {@see SessionManager} itself, based of the provided configuration.
 */
class ValidatorChain extends EventManager
{
    public function __construct(protected StorageInterface $storage)
    {
        parent::__construct();
        $validators = $storage->getMetadata('_VALID');
        if ($validators) {
            foreach ($validators as $validator => $data) {
                $this->attachValidator('session.validate', [new $validator($data), 'isValid'], 1);
            }
        }
    }

    /**
     * Attach a listener to the session validator chain.
     *
     * @param string   $eventName
     * @param int      $priority
     * @return callable
     */
    public function attach($eventName, callable $listener, $priority = 1)
    {
        return $this->attachValidator($eventName, $listener, $priority);
    }

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
     * @param string   $event
     * @param callable $callback
     * @param int      $priority
     * @return callable
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

        return parent::attach($event, $callback, $priority);
    }
}
