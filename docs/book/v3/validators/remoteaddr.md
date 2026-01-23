# Remote Addr

`Laminas\Session\Validator\RemoteAddr` provides a validator to check the session
against the user IP stored in the original `Environment` variable (passed as `$initial` to the validator).
Validation will fail in the event that this does not match and throws a
`Laminas\Session\Exception\SessionValidationFailedException` in `Laminas\Session\SessionManager`
after `session_start()` has been called.

## Supported Options

The following options are supported for `Laminas\Session\Validator\RemoteAddr`.
The options can be configured under the `config.session_manager.remoteAddressOptions` key.

| Option            | Description                                              | Optional/Mandatory |
|-------------------|----------------------------------------------------------|--------------------|
| `use_proxy`       | Whether or not to get the user's IP address from a proxy | Optional           |
| `trusted_proxies` | A list of valid proxy addresses                          | Optional           |
