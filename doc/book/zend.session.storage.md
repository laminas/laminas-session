# Session Storage

Zend Framework comes with a standard set of storage classes which are ready for you to use. Storage
handlers is the intermediary between when the session starts and when the session writes and closes.
The default session storage is `Zend\Session\Storage\SessionArrayStorage`.

orphan  

## Array Storage

`Zend\Session\Storage\ArrayStorage` provides a facility to store all information in an ArrayObject.
This storage method is likely incompatible with 3rd party libraries and all properties will be
inaccessible through the $\_SESSION property. Additionally ArrayStorage will not automatically
repopulate the storage container in the case of each new request and would have to manually be
re-populated.

### Basic Usage

A basic example is one like the following:

```php
use Zend\Session\Storage\ArrayStorage;
use Zend\Session\SessionManager;

$populateStorage = array('foo' => 'bar');
$storage         = new ArrayStorage($populateStorage);
$manager         = new SessionManager();
$manager->setStorage($storage);
```

orphan  

## Session Storage

`Zend\Session\Storage\SessionStorage` replaces $\_SESSION providing a facility to store all
information in an ArrayObject. This means that it may not be compatible with 3rd party libraries.
Although information stored in the $\_SESSION superglobal should be available in other scopes.

### Basic Usage

A basic example is one like the following:

```php
use Zend\Session\Storage\SessionStorage;
use Zend\Session\SessionManager;

$manager = new SessionManager();
$manager->setStorage(new SessionStorage());
```

orphan  

## Session Array Storage

`Zend\Session\Storage\SessionArrayStorage` provides a facility to store all information directly in
the $\_SESSION superglobal. This storage class provides the most compatibility with 3rd party
libraries and allows for directly storing information into $\_SESSION.

### Basic Usage

A basic example is one like the following:

```php
use Zend\Session\Storage\SessionArrayStorage;
use Zend\Session\SessionManager;

$manager = new SessionManager();
$manager->setStorage(new SessionArrayStorage());
```

## Custom Storage

In the event that you prefer a different type of storage; to create a new custom storage container,
you *must* implement `Zend\Session\Storage\StorageInterface` which is mostly in implementing
ArrayAccess, Traversable, Serializable and Countable. StorageInterface defines some additional
functionality that must be implemented.
