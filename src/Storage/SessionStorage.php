<?php

declare(strict_types=1);

namespace Laminas\Session\Storage;

use AllowDynamicProperties;
use ArrayIterator;
use ArrayObject;
use Iterator;

use function is_object;

/**
 * Session storage in $_SESSION
 *
 * Replaces the $_SESSION superglobal with an ArrayObject that allows for
 * property access, metadata storage, locking, and immutability.
 *
 * @template TKey of string
 * @template TValue
 * @template-extends ArrayStorage<TKey, TValue>
 */
#[AllowDynamicProperties]
class SessionStorage extends ArrayStorage
{
    /**
     * Constructor
     *
     * Sets the $_SESSION superglobal to an ArrayObject, maintaining previous
     * values if any discovered.
     *
     * @param array<TKey, TValue>|null $input
     * @param class-string<Iterator> $iteratorClass
     */
    public function __construct(
        array|null $input = null,
        int $flags = ArrayObject::ARRAY_AS_PROPS,
        string $iteratorClass = ArrayIterator::class
    ) {
        $resetSession = true;
        if ((null === $input) && isset($_SESSION)) {
            $input = $_SESSION;
            if (is_object($input) && $_SESSION instanceof ArrayObject) {
                $resetSession = false;
            } elseif (is_object($input) && ! $_SESSION instanceof ArrayObject) {
                $input = (array) $input;
            }
        } elseif (null === $input) {
            $input = [];
        }

        parent::__construct($input, $flags, $iteratorClass);
        if ($resetSession) {
            $_SESSION = $this;
        }
    }

    /**
     * Destructor
     *
     * Resets $_SESSION superglobal to an array, by casting object using
     * getArrayCopy().
     *
     * @return void
     */
    public function __destruct()
    {
        $_SESSION = $this->getArrayCopy();
    }

    /**
     * Load session object from an existing array
     *
     * Ensures $_SESSION is set to an instance of the object when complete.
     *
     * @param array<TKey, TValue> $array
     */
    public function fromArray(array $array): static
    {
        parent::fromArray($array);
        if ($_SESSION !== $this) {
            $_SESSION = $this;
        }

        return $this;
    }

    /**
     * Mark object as isImmutable
     */
    public function markImmutable(): static
    {
        $this['_IMMUTABLE'] = true;

        return $this;
    }

    /**
     * Determine if this object is isImmutable
     */
    public function isImmutable(): bool
    {
        return isset($this['_IMMUTABLE']) && $this['_IMMUTABLE'];
    }
}
