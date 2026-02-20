# Session Manager

The session manager, `Laminas\Session\SessionManager`, is the class responsible for
all aspects of session management. It initializes configuration, storage, validators
and save handlers.  Additionally the session manager can be injected into the
session container to provide a wrapper or namespace around your session data.

The session manager is responsible for starting a session, testing if a session
exists, writing to the session, regenerating the session identifier, setting the
session time-to-live, and destroying the session. The session manager can
validate sessions using the configured validators to ensure that the session data is
correct.

## Initializing the Session Manager

Generally speaking, you will always want to initialize the session manager and
ensure that your application was responsible for its initialization; this puts
in place a simple solution to prevent against session fixation. Generally you
will setup configuration and then inside of an application module bootstrap the
session manager.

Additionally you will likely want to supply validators to prevent against
session hijacking.

The following illustrates how you may configure the session manager by setting
options in your local or global config:

```php
use Laminas\Session;

return [
    'session_manager' => [
        'config' => [
            'class' => Session\Config\SessionConfig::class,
            'options' => [
                'name' => 'myapp',
            ],
        ],
        'storage' => Session\Storage\SessionArrayStorage::class,
        'validators' => [
            'classes' => [
                Session\Validator\RemoteAddr::class,
                Session\Validator\HttpUserAgent::class,
            ],
            'options' => [
                'remote_addr' => [
                    'use_proxy' => false,
                    'trusted_proxies' => [],
                ],
            ]
        ],
    ],
];
```

The following illustrates a simple `SessionMiddleware` implementation that makes use
of the session manager:

```php
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(protected SessionManager $sessionManager) {
        $this->defaultSessionManager = $sessionManager;
        Container::setDefaultManager($sessionManager);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $this->defaultSessionManager->start();

        return $handler->handle($request);
    }
}
```

When you create a new `Laminas\Session\Container` (see
[Session Container](container.md) page) in a controller, for example, it will
use the session configured above.
