# Session Save Handlers

laminas-session comes with an implementation of a save handler class.
Save handlers themselves are decoupled from PHP's save handler functions and are
only implemented as a PHP save handler when utilized in conjunction with `Laminas\Session\SessionManager`.

## Cache

`Laminas\Session\SaveHandler\Cache` allows you to provide an instance of
`Psr\SimpleCache\CacheInterface` to be utilized as a session save
handler. Generally if you are utilizing the `Cache` save handler; you are likely
using products such as memcached.

You have the flexibility to choose any `Psr\SimpleCache` implementation, such as the `SimpleCacheDecorator` from [laminas/laminas-cache](https://github.com/laminas/laminas-cache).
Once selected, this will enable you to utilize the shipped `Cache` save handler within `laminas-session`.

### Basic usage

A basic example is one like the following:

```php
use Laminas\Session\SaveHandler\Cache;
use Laminas\Session\SessionManager;

$cache       = new MemcachedAdapter(); // any adapter implementing Psr\SimpleCache\CacheInterface
$saveHandler = new Cache($cache);
$manager     = new SessionManager();
$manager->setSaveHandler($saveHandler);
```

## Custom Save Handlers

There may be cases where you want to create a save handler.  Creating a custom
save handler is much like creating a custom PHP save handler, with minor
differences. All laminas-session-compatible save handlers *must* implement
`Laminas\Session\SaveHandler\SaveHandlerInterface`.  Additionally, if your save
handler has configurable functionality, you will also need to create an options
class.
