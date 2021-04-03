# Session Validators

Session validators provide protections against session hijacking.

## Http User Agent

`Laminas\Session\Validator\HttpUserAgent` provides a validator to check the session
against the originally stored `$_SERVER['HTTP_USER_AGENT']` variable. Validation
will fail in the event that this does not match and throws an exception in
`Laminas\Session\SessionManager` after `session_start()` has been called.

### Basic Usage

```php
use Laminas\Session\Validator\HttpUserAgent;
use Laminas\Session\SessionManager;

$manager = new SessionManager();
$manager->getValidatorChain()
    ->attach('session.validate', [new HttpUserAgent(), 'isValid']);
```

## Remote Addr

`Laminas\Session\Validator\RemoteAddr` provides a validator to check the session
against the originally stored `$_SERVER['REMOTE_ADDR']` variable. Validation
will fail in the event that this does not match and throws an exception in
`Laminas\Session\SessionManager` after `session_start()` has been called.

> ### Installation Requirements
>
> The validation of the IP address depends on the [laminas-http](https://docs.laminas.dev/laminas-http/) component, so be sure to have it installed before getting started:
>
> ```bash
> $ composer require laminas/laminas-http
> ```

### Basic Usage

```php
use Laminas\Session\Validator\RemoteAddr;
use Laminas\Session\SessionManager;

$manager = new SessionManager();
$manager->getValidatorChain()
    ->attach('session.validate', [new RemoteAddr(), 'isValid']);
```

## Custom Validators

You may want to provide your own custom validators to validate against other
items from storing a token and validating a token to other various techniques.
To create a custom validator you *must* implement the validation interface
`Laminas\Session\Validator\ValidatorInterface`.
