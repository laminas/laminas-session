# Session Save Handlers

Zend Framework comes with a standard set of save handler classes which are ready for you to use.
Save Handlers themselves are decoupled from PHP's save handler functions and are *only* implemented
as a PHP save handler when utilized in conjunction with `Zend\Session\SessionManager`.

orphan  

## Cache

`Zend\Session\SaveHandler\Cache` allows you to provide an instance of `Zend\Cache` to be utilized as
a session save handler. Generally if you are utilizing the Cache save handler; you are likely using
products such as memcached.

### Basic usage

A basic example is one like the following:

```php
use Zend\Cache\StorageFactory;
use Zend\Session\SaveHandler\Cache;
use Zend\Session\SessionManager;

$cache = StorageFactory::factory(array(
    'adapter' => array(
       'name' => 'memcached',
       'options' => array(
           'server' => '127.0.0.1',
       ),
    )
));
$saveHandler = new Cache($cache);
$manager = new SessionManager();
$manager->setSaveHandler($saveHandler);
```

orphan  

## DbTableGateway

`Zend\Session\SaveHandler\DbTableGateway` allows you to utilize `Zend\Db` as a session save handler.
Setup of the DbTableGateway requires an instance of `Zend\Db\TableGateway\TableGateway` and an
instance of `Zend\Session\SaveHandler\DbTableGatewayOptions`. In the most basic setup, a
TableGateway object and using the defaults of the DbTableGatewayOptions will provide you with what
you need.

### Creating the database table

```php
CREATE TABLE `session` (
    `id` char(32),
    `name` char(32),
    `modified` int,
    `lifetime` int,
    `data` text,
     PRIMARY KEY (`id`, `name`)
);
```

### Basic usage

A basic example is one like the following:

```php
use Zend\Db\TableGateway\TableGateway;
use Zend\Session\SaveHandler\DbTableGateway;
use Zend\Session\SaveHandler\DbTableGatewayOptions;
use Zend\Session\SessionManager;

$tableGateway = new TableGateway('session', $adapter);
$saveHandler  = new DbTableGateway($tableGateway, new DbTableGatewayOptions());
$manager      = new SessionManager();
$manager->setSaveHandler($saveHandler);
```

orphan  

## MongoDB

`Zend\Session\SaveHandler\MongoDB` allows you to provide a MongoDB instance to be utilized as a
session save handler. You provide the options in the `Zend\Session\SaveHandler\MongoDBOptions`
class.

### Basic Usage

A basic example is one like the following:

```php
use Mongo;
use Zend\Session\SaveHandler\MongoDB;
use Zend\Session\SaveHandler\MongoDBOptions;
use Zend\Session\SessionManager;

$mongo = new Mongo();
$options = new MongoDBOptions(array(
    'database'   => 'myapp',
    'collection' => 'sessions',
));
$saveHandler = new MongoDB($mongo, $options);
$manager     = new SessionManager();
$manager->setSaveHandler($saveHandler);
```

## Custom Save Handlers

There may be cases where you want to create a save handler where a save handler currently does not
exist. Creating a custom save handler is much like creating a custom PHP save handler. All save
handlers *must* implement `Zend\Session\SaveHandler\SaveHandlerInterface`. Generally if your save
handler has options you will create another options class for configuration of the save handler.
