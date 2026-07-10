<?php

declare(strict_types=1);

namespace Laminas\Session;

use AllowDynamicProperties;
use ArrayIterator;
use ArrayObject;
use Laminas\Session\ManagerInterface as Manager;
use Traversable;

use function assert;
use function is_string;
use function preg_match;
use function session_status;

use const PHP_SESSION_NONE;

/**
 * @template TKey of string
 * @template TValue
 * @template-extends AbstractContainer<TKey, TValue>
 * @psalm-no-seal-properties
 */
#[AllowDynamicProperties]
class LazyContainer extends AbstractContainer
{
    /**
     * @throws Exception\InvalidArgumentException
     */
    public function __construct(string $name = 'Default', ?Manager $manager = null)
    {
        if (! preg_match('/^[a-z0-9][a-z0-9_\\\\]+$/i', $name)) {
            throw new Exception\InvalidArgumentException(
                'Name passed to container is invalid; must consist of alphanumerics, backslashes and underscores only'
            );
        }

        $this->name = $name;
        $this->setManager($manager);

        // AbstractContainer::__construct is not called here
        // because it calls $this->getManager()->start(), thus starting the session automatically
        ArrayObject::__construct([], ArrayObject::ARRAY_AS_PROPS);
    }

    private function startIfNeeded(bool $write): void
    {
        if (
            $write ||
            (session_status() === PHP_SESSION_NONE &&
            isset($_COOKIE[$this->getManager()->getName()]))
        ) {
            $this->getManager()->start();
        }
    }

    /**
     * @param TKey|non-empty-string $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->startIfNeeded(true);
        parent::offsetSet($offset, $value);
    }

    /**
     * @param array<TKey, TValue>|Traversable<TKey, TValue> $input
     * @return array<TKey, TValue>
     */
    public function exchangeArray(object|array $input): array
    {
        $this->startIfNeeded(true);
        return parent::exchangeArray($input);
    }

    /**
     * @param string|array<TKey>|null $vars
     * @return LazyContainer&static
     */
    public function setExpirationSeconds(int $ttl, string|array|null $vars = null): static
    {
        $this->startIfNeeded(true);
        return parent::setExpirationSeconds($ttl, $vars);
    }

    /**
     * @param string|array<TKey>|null $vars
     */
    public function setExpirationHops(int $hops, string|array|null $vars = null): static
    {
        $this->startIfNeeded(true);

        /** @var static $result */
        $result = parent::setExpirationHops($hops, $vars);

        return $result;
    }

    /**
     * @param TKey|non-empty-string $key
     */
    public function offsetExists(mixed $key): bool
    {
        assert(is_string($key) && $key !== '');

        $this->startIfNeeded(false);

        if (! $this->getManager()->sessionExists()) {
            return false;
        }

        return parent::offsetExists($key);
    }

    /**
     * @param TKey|non-empty-string $key
     */
    public function &offsetGet(mixed $key, mixed $default = null): mixed
    {
        assert(is_string($key) && $key !== '');

        $ret = $default;

        if (! $this->offsetExists($key)) {
            return $ret;
        }

        $storage = $this->getStorage();
        $name    = $this->getName();
        $ret     = &$storage[$name][$key];

        return $ret;
    }

    /**
     * @return Traversable<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        $this->startIfNeeded(false);

        if (! $this->getManager()->sessionExists()) {
            return new ArrayIterator([]);
        }

        return parent::getIterator();
    }

    /**
     * @return array<TKey, TValue>
     */
    public function getArrayCopy(): array
    {
        $this->startIfNeeded(false);

        if (! $this->getManager()->sessionExists()) {
            return [];
        }

        return parent::getArrayCopy();
    }
}
