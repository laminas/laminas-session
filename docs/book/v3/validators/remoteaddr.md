# Remote Addr

`Laminas\Session\Validator\RemoteAddr` provides a validator to check the session
against the originally stored `$_SERVER['REMOTE_ADDR']` variable. Validation
will fail in the event that this does not match and throws an exception in
`Laminas\Session\SessionManager` after `session_start()` has been called.

> MISSING: **Installation Requirements**
> The validation of the IP address depends on the [laminas-http](https://docs.laminas.dev/laminas-http/) component, so be sure to have it installed before getting started:
>
> ```bash
> $ composer require laminas/laminas-http
> ```

## Basic Usage

```php
$manager = new Laminas\Session\SessionManager();
$manager->getValidatorChain()->attach(
    'session.validate',
    [
        new Laminas\Session\Validator\RemoteAddr(),
        'isValid'
    ]
  );
```
