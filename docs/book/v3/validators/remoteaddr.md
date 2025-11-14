# Remote Addr

`Laminas\Session\Validator\RemoteAddr` provides a validator to check the session
against the originally stored `Environment` variable (passed as `$initial` to the validator).
Validation will fail in the event that this does not match and throws an exception in
`Laminas\Session\SessionManager` after `session_start()` has been called.

## Supported Options

The following options are supported for `Laminas\Session\Validator\RemoteAddr`.

| Option            | Description                                              | Optional/Mandatory |
|-------------------|----------------------------------------------------------|--------------------|
| `use_proxy`       | Whether or not to get the user's IP address from a proxy | Optional           |
| `trusted_proxies` | A list of valid proxy addresses                          | Optional           |
