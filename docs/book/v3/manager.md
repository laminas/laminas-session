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
        'options' => [
            'preserve_storage' => false,
            'send_expire_cookie' => true,
            'clear_storage' => false,
            'attach_default_validators' => true,
        ],
    ],
];
```

The session manager can be injected into your application's classes in order to make use of its features.
The following illustrates a simple `SessionMiddleware` implementation that uses the `SessionManager` to customize
the session before initialising it:

```php

use Laminas\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    public function __construct(protected SessionManager $sessionManager) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $this->sessionManager->setId('customSessionId');
        $this->sessionManager->setName('customSessionName');
        $this->sessionManager->start();

        return $handler->handle($request);
    }
}
```

When you create a new `Laminas\Session\Container` (see
[Session Container](container.md) page) in a controller, for example, it will
use the session configured above.
