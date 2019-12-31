# Session Save Handlers

Laminas comes with a standard set of save handler classes which are ready for you to use.
Save Handlers themselves are decoupled from PHP's save handler functions and are *only* implemented
as a PHP save handler when utilized in conjunction with `Laminas\Session\SessionManager`.

## Cache

`Laminas\Session\SaveHandler\Cache` allows you to provide an instance of `Laminas\Cache` to be utilized as
a session save handler. Generally if you are utilizing the Cache save handler; you are likely using
products such as memcached.

### Basic usage

A basic example is one like the following:

```php
use Laminas\Cache\StorageFactory;
use Laminas\Session\SaveHandler\Cache;
use Laminas\Session\SessionManager;

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

## DbTableGateway

`Laminas\Session\SaveHandler\DbTableGateway` allows you to utilize `Laminas\Db` as a session save handler.
Setup of the DbTableGateway requires an instance of `Laminas\Db\TableGateway\TableGateway` and an
instance of `Laminas\Session\SaveHandler\DbTableGatewayOptions`. In the most basic setup, a
TableGateway object and using the defaults of the DbTableGatewayOptions will provide you with what
you need.

### Creating the database table

```sql
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
use Laminas\Db\TableGateway\TableGateway;
use Laminas\Session\SaveHandler\DbTableGateway;
use Laminas\Session\SaveHandler\DbTableGatewayOptions;
use Laminas\Session\SessionManager;

$tableGateway = new TableGateway('session', $adapter);
$saveHandler  = new DbTableGateway($tableGateway, new DbTableGatewayOptions());
$manager      = new SessionManager();
$manager->setSaveHandler($saveHandler);
```

## MongoDB

`Laminas\Session\SaveHandler\MongoDB` allows you to provide a MongoDB instance to be utilized as a
session save handler. You provide the options in the `Laminas\Session\SaveHandler\MongoDBOptions`
class. You must install the [mongodb PHP extensions](http://php.net/manual/en/set.mongodb.php) and the 
[MongoDB PHP library](https://github.com/mongodb/mongo-php-library).

### Basic Usage

A basic example is one like the following:

```php
use MongoDB\Client;
use Laminas\Session\SaveHandler\MongoDB;
use Laminas\Session\SaveHandler\MongoDBOptions;
use Laminas\Session\SessionManager;

$mongoClient = new Client();
$options = new MongoDBOptions(array(
    'database'   => 'myapp',
    'collection' => 'sessions',
));
$saveHandler = new MongoDB($mongoClient, $options);
$manager     = new SessionManager();
$manager->setSaveHandler($saveHandler);
```

## Custom Save Handlers

There may be cases where you want to create a save handler where a save handler currently does not
exist. Creating a custom save handler is much like creating a custom PHP save handler. All save
handlers *must* implement `Laminas\Session\SaveHandler\SaveHandlerInterface`. Generally if your save
handler has options you will create another options class for configuration of the save handler.
