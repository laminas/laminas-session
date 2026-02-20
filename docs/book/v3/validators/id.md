# Session Id

`Laminas\Session\Validator\Id` is set as the default validator in `Laminas\Session\SessionManager`.
It provides validation of the current session id itself, verifying its existence and the character set it uses
in relation with the PHP version being used.
Validation will fail in the event that this does not match and throws a
`Laminas\Session\Exception\SessionValidationFailedException` in `Laminas\Session\SessionManager`
after `session_start()` has been called.
