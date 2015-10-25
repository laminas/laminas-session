<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace Zend\Session;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManager;
use Zend\Session\Storage\StorageInterface as Storage;
use Zend\Session\Validator\ValidatorInterface as Validator;

/**
 * Validator chain for validating sessions
 */
class ValidatorChain extends EventManager
{
    /**
     * @var Storage
     */
    protected $storage;

    /**
     * @var Event
     */
    protected $eventPrototype;

    /**
     * Construct the validation chain
     *
     * Retrieves validators from session storage and attaches them.
     *
     * @param Storage $storage
     * @param EventInterface $eventPrototype
     */
    public function __construct(Storage $storage, EventInterface $eventPrototype)
    {
        $this->storage = $storage;
        $this->eventPrototype = $eventPrototype;

        $validators = $storage->getMetadata('_VALID');
        if ($validators) {
            foreach ($validators as $validator => $data) {
                $this->attach('session.validate', new $validator($data));
            }
        }
    }

    /**
     * Attach a listener to the session validator chain
     *
     * @param  string $eventName
     * @param  callable $callback
     * @param  int $priority
     * @return \Zend\Stdlib\CallbackHandler
     */
    public function attach($eventName, callable $callback, $priority = 1)
    {
        if ($callback instanceof Validator) {
            $data = $callback->getData();
            $name = $callback->getName();
            $this->getStorage()->setMetadata('_VALID', [$name => $data]);
        }

        $listener = parent::attach($eventName, $callback, $priority);
        return $listener;
    }

    /**
     * Retrieve session storage object
     *
     * @return Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }
}
