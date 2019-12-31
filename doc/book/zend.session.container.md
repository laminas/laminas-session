# Session Container

`Laminas\Session\Container` instances provide the primary API for manipulating session data in the Laminas
Framework. Containers are used to segregate all session data, although a default namespace exists
for those who only want one namespace for all their session data.

Each instance of `Laminas\Session\Container` corresponds to an entry of the `Laminas\Session\Storage`,
where the namespace is used as the key. `Laminas\Session\Container` itself is an instance of an
ArrayObject.

## Basic Usage

```php
use Laminas\Session\Container;

$container = new Container('namespace');
$container->item = 'foo';
```

## Setting the Default Session Manager

In the event you are using multiple session managers or prefer to be explicit, the default session
manager that is utilized can be explicitly set.

```php
use Laminas\Session\Container;
use Laminas\Session\SessionManager;

$manager = new SessionManager();
Container::setDefaultManager($manager);
```
