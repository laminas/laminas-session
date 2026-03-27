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
        'enable_default_container_manager' => true,
    ],
];
```

## Supported Options

| Option                              | Data Type | Default value | Description                                                                                                     |
|-------------------------------------|-----------|---------------|-----------------------------------------------------------------------------------------------------------------|
| `validators.classes`                | `array`   | `[]`          | List of fully-qualified validator class names implementing the shipped `ValidatorInterface`.                    |
| `validators.options`                | `array`   | `[]`          | Validator specific options, keyed by validator name.                                                            |
| `options.preserve_storage`          | `boolean` | `false`       | Whether to preserve the data found on the storage object on session start.                                      |
| `options.send_expire_cookie`        | `boolean` | `true`        | Whether to send an expiry cookie the moment `SessionManager::destroy()` is called, deleting the session cookie. |
| `options.clear_storage`             | `boolean` | `false`       | Whether to clear all data from the storage object when calling `SessionManager::destroy()`.                     |
| `options.attach_default_validators` | `boolean` | `true`        | Whether to attach the default `Id` validator.                                                                   |
| `enable_default_container_manager`  | `boolean` | `true`        | Whether to inject the created manager as the default manager for `Container` instances.                         |

## Usage

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
