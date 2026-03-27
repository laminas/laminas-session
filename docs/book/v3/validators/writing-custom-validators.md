# Writing Custom Validators

Own custom validators can be provided to validate against other items from storing a token and validating a token to other various techniques.

Two validator interfaces are supported:

- Validators implementing `Laminas\Session\Validator\ValidatorInterface` can be added to the `Laminas\Session\SessionManager` config
to run on every `session_start()` call.
- Validators implementing `Laminas\Validator\ValidatorInterface` (such as the shipped `Laminas\Session\Validator\Csrf`) cannot be
wired into the session manager's automatic validation chain by default.

More information on how to create custom validators can be found in the [laminas-validator documentation](https://docs.laminas.dev/laminas-validator/writing-validators/).

## Writing Custom EnvironmentFactories

Custom `EnvironmentFactoryInterface` implementations can be added in order to facilitate writing custom validators.
The shipped validators implementing `Laminas\Session\Validator\ValidatorInterface` make use of
the `Laminas\Session\Service\GlobalEnvironmentFactory` in order to generate the `Laminas\Session\Validator\Environment`
with data from the `$_SERVER` superglobal.

If any custom validator written would need extra data that the shipped `Environment` does not provide,
a custom `Laminas\Session\Validator\EnvironmentInterface` implementation can be added, alongside a new factory handling its generation.

The new factory can be used to override the default `GlobalEnvironmentFactory` in your application's DI system,
in order to return your custom object.
