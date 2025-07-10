<?php

declare(strict_types=1);

namespace Laminas\Session;

use AllowDynamicProperties;
use ArrayIterator;
use ArrayObject;
use Iterator;
use Laminas\Session\ManagerInterface as Manager;
use Laminas\Session\Storage\StorageInterface as Storage;
use Traversable;

use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function assert;
use function is_array;
use function is_scalar;
use function is_string;
use function preg_match;
use function time;

/**
 * Session storage container
 *
 * Allows for interacting with session storage in isolated containers, which
 * may have their own expiries, or even expiries per key in the container.
 * Additionally, expiries may be absolute TTLs or measured in "hops", which
 * are based on how many times the key or container were accessed.
 *
 * @template TKey of string
 * @template TValue
 * @template-extends ArrayObject<TKey, TValue>
 */
#[AllowDynamicProperties]
abstract class AbstractContainer extends ArrayObject
{
    /**
     * Container name
     */
    protected string $name;

    protected Manager $manager;

    /**
     * Default manager class to use if no manager has been provided
     */
    protected static string $managerDefaultClass = SessionManager::class;

    /**
     * Default manager to use when instantiating a container without providing a ManagerInterface
     */
    protected static ?Manager $defaultManager = null;

    /**
     * Default value to return by reference from offsetGet
     *
     * @phpcs:disable WebimpressCodingStandard.Classes.NoNullValues.Invalid
     */
    private mixed $defaultValue = null;

    /**
     * Constructor
     *
     * Provide a name ('Default' if none provided) and a ManagerInterface instance.

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

        // Create namespace
        parent::__construct([], ArrayObject::ARRAY_AS_PROPS);

        // Start session
        $this->getManager()->start();
    }

    /**
     * Set the default ManagerInterface instance to use when none provided to constructor
     */
    public static function setDefaultManager(?Manager $manager = null): void
    {
        static::$defaultManager = $manager;
    }

    /**
     * Get the default ManagerInterface instance
     *
     * If none provided, instantiates one of type {@link $managerDefaultClass}
     *
     * @throws Exception\InvalidArgumentException If invalid manager default class provided.
     */
    public static function getDefaultManager(): Manager
    {
        if (null === static::$defaultManager) {
            $manager = new static::$managerDefaultClass();
            if (! $manager instanceof Manager) {
                throw new Exception\InvalidArgumentException(
                    'Invalid default manager type provided; must implement ManagerInterface'
                );
            }
            static::$defaultManager = $manager;
        }

        return static::$defaultManager;
    }

    /**
     * Get container name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set session manager
     *
     * @throws Exception\InvalidArgumentException
     */
    protected function setManager(?Manager $manager = null): static
    {
        if (null === $manager) {
            $manager = static::getDefaultManager();
            if (! $manager instanceof Manager) {
                throw new Exception\InvalidArgumentException(
                    'Manager provided is invalid; must implement ManagerInterface'
                );
            }
        }
        $this->manager = $manager;

        return $this;
    }

    /**
     * Get manager instance
     */
    public function getManager(): Manager
    {
        return $this->manager;
    }

    /**
     * Get session storage object
     *
     * Proxies to ManagerInterface::getStorage()
     */
    protected function getStorage(): Storage
    {
        return $this->getManager()->getStorage();
    }

    /**
     * Create a new container object on which to act
     */
    protected function createContainer(): Traversable
    {
        return new ArrayObject([], ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Verify container namespace
     *
     * Checks to see if a container exists within the Storage object already.
     * If not, one is created; if so, checks to see if it's an ArrayObject.
     * If not, it raises an exception; otherwise, it returns the Storage
     * object.
     *
     * $createContainer Whether or not to create the container for the namespace
     * Returns null only if $createContainer is false
     *
     * @throws Exception\RuntimeException
     */
    protected function verifyNamespace(bool $createContainer = true): ?Storage
    {
        $storage = $this->getStorage();
        $name    = $this->getName();
        if (! isset($storage[$name])) {
            if (! $createContainer) {
                return null;
            }
            $storage[$name] = $this->createContainer();
        }
        if (! is_array($storage[$name]) && ! $storage[$name] instanceof Traversable) {
            throw new Exception\RuntimeException('Container cannot write to storage due to type mismatch');
        }

        return $storage;
    }

    /**
     * Determine whether a given key needs to be expired
     *
     * Returns true if the key has expired, false otherwise.
     *
     * @param TKey|non-empty-string $key
     */
    protected function expireKeys(?string $key = null): bool
    {
        $storage = $this->verifyNamespace();
        $name    = $this->getName();

        // Return early if key not found
        if ((null !== $key) && ! isset($storage[$name][$key])) {
            return true;
        }

        if ($this->expireByExpiryTime($storage, $name, $key)) {
            return true;
        }

        if ($this->expireByHops($storage, $name, $key)) {
            return true;
        }

        return false;
    }

    /**
     * Expire a key by expiry time
     *
     * Checks to see if the entire container has expired based on TTL setting,
     * or the individual key.
     *
     * @param TKey|non-empty-string|null $key
     */
    protected function expireByExpiryTime(Storage $storage, string $name, ?string $key): bool
    {
        $metadata = $storage->getMetadata($name);

        // Global container expiry
        if (
            is_array($metadata)
            && isset($metadata['EXPIRE'])
            && ($_SERVER['REQUEST_TIME'] > $metadata['EXPIRE'])
        ) {
            unset($metadata['EXPIRE']);
            $storage->setMetadata($name, $metadata, true);
            $storage[$name] = $this->createContainer();

            return true;
        }

        // Expire individual key
        if (
            (null !== $key)
            && is_array($metadata)
            && isset($metadata['EXPIRE_KEYS'])
            && isset($metadata['EXPIRE_KEYS'][$key])
            && ($_SERVER['REQUEST_TIME'] > $metadata['EXPIRE_KEYS'][$key])
        ) {
            unset($metadata['EXPIRE_KEYS'][$key]);
            $storage->setMetadata($name, $metadata, true);
            unset($storage[$name][$key]);

            return true;
        }

        // Find any keys that have expired
        if (
            (null === $key)
            && is_array($metadata)
            && isset($metadata['EXPIRE_KEYS'])
        ) {
            foreach (array_keys($metadata['EXPIRE_KEYS']) as $key) {
                if ($_SERVER['REQUEST_TIME'] > $metadata['EXPIRE_KEYS'][$key]) {
                    unset($metadata['EXPIRE_KEYS'][$key]);
                    if (isset($storage[$name][$key])) {
                        unset($storage[$name][$key]);
                    }
                }
            }
            $storage->setMetadata($name, $metadata, true);

            return true;
        }

        return false;
    }

    /**
     * Expire key by session hops
     *
     * Determines whether the container or an individual key within it has
     * expired based on session hops
     *
     * @param TKey|non-empty-string|null $key
     */
    protected function expireByHops(Storage $storage, string $name, ?string $key): bool
    {
        $ts       = $storage->getRequestAccessTime();
        $metadata = $storage->getMetadata($name);

        // Global container expiry
        if (
            is_array($metadata)
            && isset($metadata['EXPIRE_HOPS'])
            && ($ts > $metadata['EXPIRE_HOPS']['ts'])
        ) {
            $metadata['EXPIRE_HOPS']['hops']--;
            if (-1 === $metadata['EXPIRE_HOPS']['hops']) {
                unset($metadata['EXPIRE_HOPS']);
                $storage->setMetadata($name, $metadata, true);
                $storage[$name] = $this->createContainer();

                return true;
            }
            $metadata['EXPIRE_HOPS']['ts'] = $ts;
            $storage->setMetadata($name, $metadata, true);

            return false;
        }

        // Single key expiry
        if (
            (null !== $key)
            && is_array($metadata)
            && isset($metadata['EXPIRE_HOPS_KEYS'])
            && isset($metadata['EXPIRE_HOPS_KEYS'][$key])
            && ($ts > $metadata['EXPIRE_HOPS_KEYS'][$key]['ts'])
        ) {
            $metadata['EXPIRE_HOPS_KEYS'][$key]['hops']--;
            if (-1 === $metadata['EXPIRE_HOPS_KEYS'][$key]['hops']) {
                unset($metadata['EXPIRE_HOPS_KEYS'][$key]);
                $storage->setMetadata($name, $metadata, true);
                unset($storage[$name][$key]);

                return true;
            }
            $metadata['EXPIRE_HOPS_KEYS'][$key]['ts'] = $ts;
            $storage->setMetadata($name, $metadata, true);

            return false;
        }

        // Find all expired keys
        if (
            (null === $key)
            && is_array($metadata)
            && isset($metadata['EXPIRE_HOPS_KEYS'])
        ) {
            foreach (array_keys($metadata['EXPIRE_HOPS_KEYS']) as $key) {
                if ($ts > $metadata['EXPIRE_HOPS_KEYS'][$key]['ts']) {
                    $metadata['EXPIRE_HOPS_KEYS'][$key]['hops']--;
                    if (-1 === $metadata['EXPIRE_HOPS_KEYS'][$key]['hops']) {
                        unset($metadata['EXPIRE_HOPS_KEYS'][$key]);
                        $storage->setMetadata($name, $metadata, true);
                        unset($storage[$name][$key]);
                        continue;
                    }
                    $metadata['EXPIRE_HOPS_KEYS'][$key]['ts'] = $ts;
                }
            }
            $storage->setMetadata($name, $metadata, true);

            return false;
        }

        return false;
    }

    /**
     * Get Offset
     *
     * @param TKey|non-empty-string $key
     */
    public function &__get(string $key): mixed
    {
        return $this->offsetGet($key);
    }

    /**
     * Set Offset
     *
     * @param TKey|non-empty-string $key
     */
    public function __set(string $key, mixed $value): void
    {
        $this->offsetSet($key, $value);
    }

    /**
     * Store a value within the container
     *
     * @param TKey|non-empty-string $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        assert($offset !== '');
        $this->expireKeys($offset);
        $storage                 = $this->verifyNamespace();
        $name                    = $this->getName();
        $storage[$name][$offset] = $value;
    }

    /**
     * Determine if the key exists
     *
     * @param TKey|non-empty-string $key
     */
    public function offsetExists(mixed $key): bool
    {
        assert(is_string($key) && $key !== '');
        // If no container exists, we can't inspect it
        if (null === ($storage = $this->verifyNamespace(false))) {
            return false;
        }
        $name = $this->getName();

        // Return early if the key isn't set
        if (! isset($storage[$name][$key])) {
            return false;
        }

        $expired = $this->expireKeys($key);

        return ! $expired;
    }

    /**
     * Retrieve a specific key in the container
     *
     * @param TKey|non-empty-string $key
     */
    public function &offsetGet(mixed $key): mixed
    {
        assert(is_string($key) && $key !== '');
        if (! $this->offsetExists($key)) {
            return $this->defaultValue;
        }
        $storage = $this->getStorage();
        $name    = $this->getName();

        return $storage[$name][$key];
    }

    /**
     * Unset a single key in the container
     *
     * @param TKey|non-empty-string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        assert($offset !== '');
        if (! $this->offsetExists($offset)) {
            return;
        }
        $storage = $this->getStorage();
        $name    = $this->getName();
        unset($storage[$name][$offset]);
    }

    /**
     * Exchange the array for another one.
     *
     * @param array<TKey, TValue>|object $input
     * @return array<TKey, TValue>
     */
    public function exchangeArray(mixed $input): array
    {
        // handle arrayobject, iterators and the like:
        if ($input instanceof ArrayObject) {
            $input = $input->getArrayCopy();
        }
        if (! is_array($input)) {
            $input = (array) $input;
        }

        $storage = $this->verifyNamespace();
        $name    = $this->getName();

        $old            = $storage[$name];
        $storage[$name] = $input;
        if ($old instanceof ArrayObject) {
            /** @var array<TKey, TValue> $array */
            $array = $old->getArrayCopy();
            return $array;
        }

        /** @var array<TKey, TValue> $array */
        $array = $old;
        return $array;
    }

    /**
     * Create a new iterator from an ArrayObject instance
     */
    public function getIterator(): Iterator
    {
        $this->expireKeys();
        $storage   = $this->getStorage();
        $container = $storage[$this->getName()];
        if ($container instanceof Iterator) {
            return $container;
        }
        return new ArrayIterator($container);
    }

    /**
     * Set expiration TTL
     *
     * Set the TTL for the entire container, a single key, or a set of keys.
     */
    public function setExpirationSeconds(int $ttl, string|array|null $vars = null): static
    {
        $storage = $this->getStorage();
        $ts      = time() + $ttl;
        if (is_scalar($vars)) {
            $vars = (array) $vars;
        }

        if (null === $vars) {
            $this->expireKeys(); // first we need to expire global key, since it can already be expired
            $data = ['EXPIRE' => $ts];
        } else {
            // Cannot pass "$this" to a lambda
            $container = $this;

            // Filter out any items not in our container
            $expires = array_filter($vars, static fn($value): bool => $container->offsetExists($value));

            // Map item keys => timestamp
            $expires = array_flip($expires);
            $expires = array_map(static fn() => $ts, $expires);

            // Create metadata array to merge in
            $data = ['EXPIRE_KEYS' => $expires];
        }

        $storage->setMetadata(
            $this->getName(),
            $data
        );

        return $this;
    }

    /**
     * Set expiration hops for the container, a single key, or set of keys
     */
    public function setExpirationHops(int $hops, string|array|null $vars = null): static
    {
        $storage = $this->getStorage();
        $ts      = $storage->getRequestAccessTime();

        if (is_scalar($vars)) {
            $vars = (array) $vars;
        }

        if (null === $vars) {
            $this->expireKeys(); // first we need to expire global key, since it can already be expired
            $data = ['EXPIRE_HOPS' => ['hops' => $hops, 'ts' => $ts]];
        } else {
            // Cannot pass "$this" to a lambda
            $container = $this;

            // FilterInterface out any items not in our container
            $expires = array_filter($vars, static fn($value): bool => $container->offsetExists($value));

            // Map item keys => timestamp
            $expires = array_flip($expires);
            $expires = array_map(static fn() => ['hops' => $hops, 'ts' => $ts], $expires);

            // Create metadata array to merge in
            $data = ['EXPIRE_HOPS_KEYS' => $expires];
        }

        $storage->setMetadata(
            $this->getName(),
            $data
        );

        return $this;
    }

    /** @inheritDoc */
    public function getArrayCopy(): array
    {
        $storage   = $this->verifyNamespace();
        $container = $storage[$this->getName()];

        if ($container instanceof ArrayObject) {
            /** @var array<TKey, TValue> $array */
            $array = $container->getArrayCopy();
            return $array;
        }

        /** @var array<TKey, TValue> $array */
        $array = $container;
        return $array;
    }
}
