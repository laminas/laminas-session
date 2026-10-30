# Session Config

laminas-session comes with a standard set of config classes, allowing setting where
a cookie lives, session lifetime, and even configuration of ext/session when
using `Laminas\Session\Config\SessionConfig`.

## Standard Config

`Laminas\Session\Config\StandardConfig` provides the base interface for
configuring sessions when *not* leveraging ext/session. This is utilized more
for specialized cases, such as when you might have session management done by
another system, or when testing.

### Basic Configuration Options

The following configuration options are defined by `Laminas\Session\Config\StandardConfig`.

Option                    | Data Type | Description
------------------------- | --------- | -----------
`cache_expire`            | `integer` | Specifies time-to-live for cached session pages in minutes.
`cookie_domain`           | `string`  | Specifies the domain to set in the session cookie.
`cookie_httponly`         | `boolean` | Marks the cookie as accessible only through the HTTP protocol.
`cookie_lifetime`         | `integer` | Specifies the lifetime of the cookie in seconds which is sent to the browser.
`cookie_path`             | `string`  | Specifies path to set in the session cookie.
`cookie_samesite`         | `string`  | Specifies whether cookies should be sent along with cross-site requests. (Since 2.11.0)
`cookie_secure`           | `boolean` | Specifies whether cookies should only be sent over secure connections.
`gc_maxlifetime`          | `integer` | Specifies the number of seconds after which data will be seen as ‘garbage’.
`gc_divisor`              | `integer` | Defines the divisor that the gc process uses to calculate the probability to start on every session initialization.
`gc_probability`          | `integer` | Defines the probability that the gc process is started on every session initialization.
`name`                    | `string`  | Specifies the name of the session which is used as cookie name.
`remember_me_seconds`     | `integer` | Specifies how long to remember the session before clearing data.
`save_path`               | `string`  | Defines the argument which is passed to the save handler.
`use_cookies`             | `boolean` | Specifies whether the module will use cookies to store the session id.

### Basic Usage

```php
use Laminas\Session\Config\StandardConfig;
use Laminas\Session\SessionManager;

$config = new StandardConfig();
$config->setOptions([
    'remember_me_seconds' => 1800,
    'name'                => 'laminas',
]);
$manager = new SessionManager($config);
```

## Session Config

`Laminas\Session\Config\SessionConfig` provides an interface for configuring
sessions that leverage PHP's ext/session. Most configuration options configure
either the `Laminas\Session\Storage` OR configure ext/session directly.

### Basic Configuration Options

The following configuration options are defined by `Laminas\Session\Config\SessionConfig`;
note that it inherits all configuration from
`Laminas\Session\Config\StandardConfig`.

 Option                   | Data Type | Description
--------------------------|-----------|-------------------------------------------------------------------------------------------
 `cache_limiter`          | `string`  | Specifies the cache control method used for session pages.
 `php_save_handler`       | `string`  | Defines the name of a PHP save_handler embedded into PHP.
 `serialize_handler`      | `string`  | Defines the name of the handler which is used to serialize/deserialize data.
 `url_rewriter_tags`      | `string`  | Specifies which HTML tags are rewritten to include session id if transparent sid enabled.
 `use_trans_sid`          | `boolean` | Whether transparent sid support is enabled or not.
 `sid_length`             | `integer` | Specifies the length of session ID string. **As of PHP 8.4**, changing this value from its default is deprecated.
 `sid_bits_per_character` | `integer` | Specifies the number of bits in encoded session ID character. **As of PHP 8.4**, changing this value from its default is deprecated.
 `use_only_cookies`       | `boolean` | Whether the module will only use cookies to store the session id on the client side. **As of PHP 8.4**, disabling this setting is deprecated.
 `save_handler`           | `string`  | Defines the name of the handler which is used for storing and retrieving data associated with a session.

### Basic Usage

```php
use Laminas\Session\Config\SessionConfig;
use Laminas\Session\SessionManager;

$config = new SessionConfig();
$config->setOptions([
    'save_handler' => 'redis',
    'save_path' => 'tcp://127.0.0.1:6379?weight=1&timeout=1',
]);
$manager = new SessionManager($config);
```

### Service Manager Factory

laminas-session ships with a [laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/)
factory which reads configuration data from the application configuration and
injects a corresponding instance of `Laminas\Session\Config\SessionConfig` into the
session manager automatically.

To use this factory, you first need to register it with the service manager by adding the
appropriate factory definition:

```php
'service_manager' => [
    'factories' => [
        'Laminas\Session\Config\ConfigInterface' => 'Laminas\Session\Service\SessionConfigFactory',
    ],
],
```

Then place your application's session configuration in the root-level
configuration key `session_config`:

```php
'session_config' => [
    'save_handler' => 'redis',
    'save_path' => 'tcp://127.0.0.1:6379?weight=1&timeout=1',
],
```

Any of the configuration options defined for [SessionConfig](#session-config) can be used
there, as well as the following factory-specific configuration options:

Option         | Data Type | Description
-------------- | --------- | -----------
`config_class` | `string`  | Name of the class to use as the configuration container (Defaults to `Laminas\Session\Config\SessionConfig`)

## Custom Configuration

In the event that you prefer to create your own session configuration; you
*must* implement `Laminas\Session\Config\ConfigInterface` which contains the basic
interface for items needed when implementing a session. This includes cookie
configuration, lifetime, session name, save path, and an interface for getting
and setting options.
