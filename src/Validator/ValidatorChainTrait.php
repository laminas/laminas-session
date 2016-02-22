<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zend-validator for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
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
     * Construct the validation chain
     *
     * Retrieves validators from session storage and attaches them.
     *
     * @param StorageInterface $storage
     */
    public function __construct(StorageInterface $storage)
    {
        parent::__construct();

        $this->storage = $storage;
        $validators = $storage->getMetadata('_VALID');
        if ($validators) {
            foreach ($validators as $validator => $data) {
                $this->attachValidator('session.validate', [new $validator($data), 'isValid'], 1);
            }
        }
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
