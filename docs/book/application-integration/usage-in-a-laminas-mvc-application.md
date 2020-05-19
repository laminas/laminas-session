# Usage in a laminas-mvc Application

The following example shows _one_ potential use case of laminas-session within
a laminas-mvc based application. The example uses a module, a controller and the
[session container](../container.md).

The example is based on the [tutorial application](https://docs.laminas.dev/tutorials/getting-started/overview/),
which builds an album inventory system.

Before starting, make sure laminas-session is installed and configured.

## Set up Configuration

To use a session container some configuration for the component is needed:

- a name for the container
- a [storage handler](../storage.md)
- some [configuration for the session](../config.md) itself

To allow a [reflection-based approach](https://docs.laminas.dev/laminas-servicemanager/reflection-abstract-factory/)
to retrieve the session container from the service manager, a class name is
needed as the name for the container. The example uses the name
`Laminas\Session\Container::class`.

Add the following lines to the local or global configuration file, e.g.
`config/autoload/global.config.php`:

```php
return [
    'session_containers' => [
        Laminas\Session\Container::class,
    ],
    'session_storage' => [
        'type' => Laminas\Session\Storage\SessionArrayStorage::class,
    ],
    'session_config'  => [
        'gc_maxlifetime' => 7200,
        // …
    ],
    // …
];
```

> ### Session Configuration is Optional
>
> The configuration for the session itself is optional, but the
> [factory `Laminas\Session\Config\SessionConfig`](../config.md#service-manager-factory),
> which is registered for configuration data, expects an array under the key
> `session_config`.  
>
> A minimum configuration is:
>
> ```php
> 'session_config'  => [],
> ```

## Create and Register Controller

[Create a controller class](https://docs.laminas.dev/laminas-mvc/quick-start/#create-a-controller)
and inject the session container with the registered classname,
e.g. `module/Album/Controller/AlbumController.php`:

```php
namespace Album\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container as SessionContainer;

class AlbumController extends AbstractActionController
{
    /** @var SessionContainer */
    private $sessionContainer;

    public function __construct(SessionContainer $sessionContainer)
    {
        $this->sessionContainer = $sessionContainer;
    }
}
```

To [register the controller](https://docs.laminas.dev/laminas-mvc/quick-start/#create-a-route)
for the application, extend the configuration of the module.  
Add the following lines to the module configuration file, e.g.
`module/Album/config/module.config.php`:

<pre class="language-php" data-line="8-9"><code>
namespace Album;

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

return [
    'controllers' => [
        'factories' => [
            // Add this line
            Controller\AlbumController::class => ReflectionBasedAbstractFactory::class,
        ],
    ],
    // …
];
</code></pre>

The example uses the [reflection factory from laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/reflection-abstract-factory/)
to resolve the constructor dependencies for the controller class.

## Writing and Reading Session Data

Using the session container in the controller, e.g.
`module/Album/Controller/AlbumController.php`:

### Write Data

```php
public function indexAction()
{
    $this->sessionContainer->album = 'I got a new CD with awesome music.';

    return [];
}
```

### Read Data

```php
public function addAction()
{
    return [
        'album_message' => $this->sessionContainer->album ?? null,
    ];
}
```

