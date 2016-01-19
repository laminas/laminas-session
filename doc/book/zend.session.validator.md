# Session Validators

Session validators provide various protection against session hijacking. Session hijacking in
particular has various drawbacks when you are protecting against it. Such as an IP address may
change from the end user depending on their ISP; or a browsers user agent may change during the
request either by a web browser extension OR an upgrade that retains session cookies.

orphan  

## Http User Agent

`Zend\Session\Validator\HttpUserAgent` provides a validator to check the session against the
originally stored $\_SERVER\['HTTP\_USER\_AGENT'\] variable. Validation will fail in the event that
this does not match and throws an exception in `Zend\Session\SessionManager` after session\_start()
has been called.

### Basic Usage

A basic example is one like the following:

```php
use Zend\Session\Validator\HttpUserAgent;
use Zend\Session\SessionManager;

$manager = new SessionManager();
$manager->getValidatorChain()->attach('session.validate', array(new HttpUserAgent(), 'isValid'));
```

orphan  

## Remote Addr

`Zend\Session\Validator\RemoteAddr` provides a validator to check the session against the originally
stored $\_SERVER\['REMOTE\_ADDR'\] variable. Validation will fail in the event that this does not
match and throws an exception in `Zend\Session\SessionManager` after session\_start() has been
called.

### Basic Usage

A basic example is one like the following:

```php
use Zend\Session\Validator\RemoteAddr;
use Zend\Session\SessionManager;

$manager = new SessionManager();
$manager->getValidatorChain()->attach('session.validate', array(new RemoteAddr(), 'isValid'));
```

## Custom Validators

You may want to provide your own custom validators to validate against other items from storing a
token and validating a token to other various techniques. To create a custom validator you *must*
implement the validation interface `Zend\Session\Validator\ValidatorInterface`.
