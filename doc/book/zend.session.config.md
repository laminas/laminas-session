# Session Config

Zend Framework comes with a standard set of config classes which are ready for you to use. Config
handles setting various configuration such as where a cookie lives, lifetime, including several bits
to configure ext/session when using `Zend\Session\Config\SessionConfig`.

orphan  

## Standard Config

`Zend\Session\Config\StandardConfig` provides you a basic interface for implementing sessions when
*not* leveraging ext/session. This is utilized more for specialized cases such as when you might
have session management done by another system.

### Basic Configuration Options

The following configuration options are defined by `Zend\Session\Config\StandardConfig`.

<table>
<colgroup>
<col width="19%" />
<col width="10%" />
<col width="70%" />
</colgroup>
<thead>
<tr class="header">
<th align="left">Option</th>
<th align="left">Data Type</th>
<th align="left">Description</th>
</tr>
</thead>
<tbody>
<tr class="odd">
<td align="left">cache_expire</td>
<td align="left"><code>integer</code></td>
<td align="left">Specifies time-to-live for cached session pages in minutes.</td>
</tr>
<tr class="even">
<td align="left">cookie_domain</td>
<td align="left"><code>string</code></td>
<td align="left">Specifies the domain to set in the session cookie.</td>
</tr>
<tr class="odd">
<td align="left">cookie_httponly</td>
<td align="left"><code>boolean</code></td>
<td align="left">Marks the cookie as accessible only through the HTTP protocol.</td>
</tr>
<tr class="even">
<td align="left">cookie_lifetime</td>
<td align="left"><code>integer</code></td>
<td align="left">Specifies the lifetime of the cookie in seconds which is sent to the browser.</td>
</tr>
<tr class="odd">
<td align="left">cookie_path</td>
<td align="left"><code>string</code></td>
<td align="left">Specifies path to set in the session cookie.</td>
</tr>
<tr class="even">
<td align="left">cookie_secure</td>
<td align="left"><code>boolean</code></td>
<td align="left">Specifies whether cookies should only be sent over secure connections.</td>
</tr>
<tr class="odd">
<td align="left">entropy_length</td>
<td align="left"><code>integer</code></td>
<td align="left">Specifies the number of bytes which will be read from the file specified in
entropy_file.</td>
</tr>
<tr class="even">
<td align="left">entropy_file</td>
<td align="left"><code>string</code></td>
<td align="left">Defines a path to an external resource (file) which will be used as an additional
entropy.</td>
</tr>
<tr class="odd">
<td align="left">gc_maxlifetime</td>
<td align="left"><code>integer</code></td>
<td align="left">Specifies the number of seconds after which data will be seen as 'garbage'.</td>
</tr>
<tr class="even">
<td align="left">gc_divisor</td>
<td align="left"><code>integer</code></td>
<td align="left">Defines the probability that the gc process is started on every session
initialization.</td>
</tr>
<tr class="odd">
<td align="left">gc_probability</td>
<td align="left"><code>integer</code></td>
<td align="left">Defines the probability that the gc process is started on every session
initialization.</td>
</tr>
<tr class="even">
<td align="left">hash_bits_per_character</td>
<td align="left"><code>integer</code></td>
<td align="left">Defines how many bits are stored in each character when converting the binary hash
data.</td>
</tr>
<tr class="odd">
<td align="left">name</td>
<td align="left"><code>string</code></td>
<td align="left">Specifies the name of the session which is used as cookie name.</td>
</tr>
<tr class="even">
<td align="left">remember_me_seconds</td>
<td align="left"><code>integer</code></td>
<td align="left">Specifies how long to remember the session before clearing data.</td>
</tr>
<tr class="odd">
<td align="left">save_path</td>
<td align="left"><code>string</code></td>
<td align="left">Defines the argument which is passed to the save handler.</td>
</tr>
<tr class="even">
<td align="left">use_cookies</td>
<td align="left"><code>boolean</code></td>
<td align="left">Specifies whether the module will use cookies to store the session id.</td>
</tr>
</tbody>
</table>

### Basic Usage

A basic example is one like the following:

```php
use Zend\Session\Config\StandardConfig;
use Zend\Session\SessionManager;

$config = new StandardConfig();
$config->setOptions(array(
    'remember_me_seconds' => 1800,
    'name'                => 'zf2',
));
$manager = new SessionManager($config);
```

orphan  

## Session Config

`Zend\Session\Config\SessionConfig` provides you a basic interface for implementing sessions when
that leverage PHP's ext/session. Most configuration options configure either the
`Zend\Session\Storage` OR configure ext/session directly.

### Basic Configuration Options

The following configuration options are defined by `Zend\Session\Config\SessionConfig`, note that it
inherits all configuration from `Zend\Session\Config\StandardConfig`.

<table>
<colgroup>
<col width="19%" />
<col width="10%" />
<col width="70%" />
</colgroup>
<thead>
<tr class="header">
<th align="left">Option</th>
<th align="left">Data Type</th>
<th align="left">Description</th>
</tr>
</thead>
<tbody>
<tr class="odd">
<td align="left">cache_limiter</td>
<td align="left"><code>string</code></td>
<td align="left">Specifies the cache control method used for session pages.</td>
</tr>
<tr class="even">
<td align="left">hash_function</td>
<td align="left"><code>string</code></td>
<td align="left">Allows you to specify the hash algorithm used to generate the session IDs.</td>
</tr>
<tr class="odd">
<td align="left">php_save_handler</td>
<td align="left"><code>string</code></td>
<td align="left">Defines the name of a PHP save_handler embedded into PHP.</td>
</tr>
<tr class="even">
<td align="left">serialize_handler</td>
<td align="left"><code>string</code></td>
<td align="left">Defines the name of the handler which is used to serialize/deserialize data.</td>
</tr>
<tr class="odd">
<td align="left">url_rewriter_tags</td>
<td align="left"><code>string</code></td>
<td align="left">Specifies which HTML tags are rewritten to include session id if transparent sid
enabled.</td>
</tr>
<tr class="even">
<td align="left">use_trans_sid</td>
<td align="left"><code>boolean</code></td>
<td align="left">Whether transparent sid support is enabled or not.</td>
</tr>
</tbody>
</table>

### Basic Usage

A basic example is one like the following:

```php
use Zend\Session\Config\SessionConfig;
use Zend\Session\SessionManager;

$config = new SessionConfig();
$config->setOptions(array(
    'phpSaveHandler' => 'redis',
    'savePath' => 'tcp://127.0.0.1:6379?weight=1&timeout=1',
));
$manager = new SessionManager($config);
```

### Service Manager Factory

`Zend\Session` ships with a Service Manager &lt;zend.service-manager.intro&gt; factory which reads
configuration data from the application configuration and injects a corresponding instance of
`Zend\Session\Config\SessionConfig` into the session manager automatically.

To use this factory, you first need to register it with the Service Manager by adding the
appropriate factory definition:

```php
'service_manager' => array(
    'factories' => array(
        'Zend\Session\Config\ConfigInterface' => 'Zend\Session\Service\SessionConfigFactory',
    ),
),
```

Then place your application's session configuration in the root-level configuration key
`session_config`:

```php
'session_config' => array(
    'phpSaveHandler' => 'redis',
    'savePath' => 'tcp://127.0.0.1:6379?weight=1&timeout=1',
),
```

Any of the configuration options defined in zend.session.config.session-config.options can be used
there, as well as the following factory-specific configuration options:

<table>
<colgroup>
<col width="19%" />
<col width="10%" />
<col width="70%" />
</colgroup>
<thead>
<tr class="header">
<th align="left">Option</th>
<th align="left">Data Type</th>
<th align="left">Description</th>
</tr>
</thead>
<tbody>
<tr class="odd">
<td align="left">config_class</td>
<td align="left"><code>string</code></td>
<td align="left">Name of the class to use as the configuration container (Defaults to
<code>Zend\Session\Config\SessionConfig</code></td>
</tr>
</tbody>
</table>

## Custom Configuration

In the event that you prefer to create your own session configuration; you *must* implement
`Zend\Session\Config\ConfigInterface` which contains the basic interface for items needed when
implementing a session. This includes cookie configuration, lifetime, session name, save path and an
interface for getting and setting options.
