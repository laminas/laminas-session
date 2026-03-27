# Session Storage

laminas-session comes with a standard set of storage handlers. Storage handlers are
the intermediary between when the session starts and when the session writes and
closes. The default session storage is `Laminas\Session\Storage\SessionArrayStorage`.

The session storage is configured under the `session_config` key:

```php
    'session_storage' => [
        'type' => SessionArrayStorage::class,
        'options' => [
            'input' => [],
            'flags' => ArrayObject::ARRAY_AS_PROPS,
            'iterator_class' => ArrayIterator::class,
        ],
    ],
```

## Supported Options

| Option           | Data Type      | Default value               | Description                                                                                              |
|------------------|----------------|-----------------------------|----------------------------------------------------------------------------------------------------------|
| `input`          | `mixed`        | situation specific          | Used to populate the storage with initial data.                                                          |
| `flags`          | `integer`      | ArrayObject::ARRAY_AS_PROPS | Specifies the `ArrayObject` flags to use for `ArrayStorage`, `SessionStorage` or any custom descendants. |
| `iterator_class` | `class-string` | ArrayIterator               | Specifies the iterator class to use for `ArrayStorage`, `SessionStorage` or any custom descendants.      |

Note that the `input` accepted values depend entirely on the set `type`, as follows:

- `SessionArrayStorage`: `null` (falls back to `$_SESSION ?? []`), `array` or an `ArrayAccess` implementation.
- `SessionStorage`: `null` (checks `$_SESSION` to reuse, defaulting to `[]` if not available) or `array`.
- `ArrayStorage`: `array`, defaulting to `[]`.

Classes extending `AbstractSessionArrayStorage` will only use the `input` option.

## Array Storage

`Laminas\Session\Storage\ArrayStorage` provides a facility to store all information
in an `ArrayObject`. This storage method is likely incompatible with 3rd party
libraries and all properties will be inaccessible through the `$_SESSION`
superglobal. Additionally `ArrayStorage` will not automatically repopulate the
storage container in the case of each new request and would have to manually be
re-populated.

### Basic Usage

```php
use Laminas\Session\Storage\ArrayStorage;
use Laminas\Session\SessionManager;

$populateStorage = ['foo' => 'bar'];
$storage         = new ArrayStorage($populateStorage);
$manager         = new SessionManager();
$manager->setStorage($storage);
```

## Session Storage

`Laminas\Session\Storage\SessionStorage` replaces `$_SESSION,` providing a facility
to store all information in an `ArrayObject`. This means that it may not be
compatible with 3rd party libraries, although information stored in the
`$_SESSION` superglobal should be available in other scopes.

### Basic Usage

```php
use Laminas\Session\Storage\SessionStorage;
use Laminas\Session\SessionManager;

$manager = new SessionManager();
$manager->setStorage(new SessionStorage());
```

## Session Array Storage

`Laminas\Session\Storage\SessionArrayStorage` provides a facility to store all
information directly in the `$_SESSION` superglobal. This storage class provides
the most compatibility with 3rd party libraries and allows for directly storing
information into `$_SESSION`.

### Basic Usage

```php
use Laminas\Session\Storage\SessionArrayStorage;
use Laminas\Session\SessionManager;

$manager = new SessionManager();
$manager->setStorage(new SessionArrayStorage());
```

## Custom Storage

To create a custom storage container, you *must* implement
`Laminas\Session\Storage\StorageInterface`. This interface extends each of
`ArrayAccess`, `Traversable`, `Serializable`, and `Countable`, and it is in the
methods those define that the majority of implementation occurs. The following
methods must also be implemented:

```php
public function getRequestAccessTime() : int;

public function lock(int|string $key = null) : void;
public function isLocked(int|string $key = null) : bool;
public function unlock(int|string $key = null) : void;

public function markImmutable() : void;
public function isImmutable() : bool;

public function setMetadata(string $key, mixed $value, bool $overwriteArray = false) : void;
public function getMetadata(string $key = null) : mixed;

public function clear(int|string $key = null) : void;

public function fromArray(array $array) : void;
public function toArray(bool $metaData = false) : array;
```
