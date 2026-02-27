# Introduction

laminas-session provides a set of validators that provide protections against session hijacking and against unauthorized requests.

The following validators are intended to be used via the configuration of `Laminas\Session\SessionManager`:

- [Id](id.md)
- [Http User Agent](httpuseragent.md)
- [Remote Addr](remoteaddr.md)

To make use of them, update the `session_manager.validators` configuration key with your specific requirements.

- `session_manager.validator.classes` allows adding your desired `Laminas\Session\Validator\ValidatorInterface`
implementations in order for the session manager to use them.
- `session_manager.validator.options` allows providing extra configuration options for any validator needing them,
such as the shipped `RemoteAddr` validator.

The `Csrf` validator is based on Laminas component for validation of data and files: [laminas-validator](https://docs.laminas.dev/laminas-validator/).
As such, its usage differs from the other shipped validators.

- [Csrf](csrf.md)
