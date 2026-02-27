# Http User Agent

`Laminas\Session\Validator\HttpUserAgent` provides a validator to check the session
against the originally stored `$_SERVER['HTTP_USER_AGENT']` variable. Validation
will fail in the event that this does not match and throws an exception in
`Laminas\Session\SessionManager` after `session_start()` has been called.
