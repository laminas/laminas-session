# Preparing for Version 3

Version 3 will introduce a number of backward incompatible changes.
This document is intended to help you prepare for these changes.

## Upcoming Behaviour & Signature Changes

### laminas-mvc Incompatibility

Version 3 will make `laminas-session` incompatible with use in `laminas-mvc` applications.
If your application relies on `laminas-mvc`, you will need to plan for this before upgrading.

### Dependencies Version Bumps

Version 3.0 will update the `laminas/laminas-servicemanager` dependency to version 4 and `laminas/laminas-validator`
to version 3. If your project has other dependencies constrained to `laminas-servicemanager` 3.x (such as
`laminas-captcha` 2.x), you will have a conflict that must be resolved before upgrading.

Any custom code using `laminas/laminas-validator` 2.x will also need to be verified and updated as necessary.

### SessionManager Changes

The `SessionManager` class will be marked as `final` in version 3.0, so it can no longer be extended.
Plan to remove any classes that extend `SessionManager`.

The `setId()` method will only accept `string` type, dropping support for the previous `int|string` union type.

Options like `send_expire_cookie`, `clear_storage`, and `preserve_storage` will be settable during construction
and will be used in `destroy()` and `start()` method calls when the parameter is passed as `null`.

The `isValid` method will no longer return a boolean value — validation failures will throw
`SessionValidationFailedException` instead.

The `SessionManager` will no longer use `ValidatorChain` internally. Any requested validators are to be added
to the `SessionManager` config section directly in order to be checked. The following properties will be removed:

- `defaultDestroyOptions`
- `defaultOptions`
- `defaultValidators`
- `validatorChain`

The following methods will also be removed:

- `initializeValidatorChain`
- `setValidatorChain`
- `getValidatorChain`

The constructor's signature will also be updated to accommodate the new usage in version 3.

### Native Types for `ManagerInterface`

Starting from version 3.0, the `ManagerInterface` will use native PHP type hints for all its methods.

If you have custom classes implementing `ManagerInterface`, you will need to update your method signatures
to match the new interface.

### ConfigProvider Changes

The `ConfigProvider` will be made `final` in version 3.0 so it will no longer be possible to extend.
In the current version it has been annotated as `final`.

### Final Validator Classes

All validator classes included in `laminas-session` will be set as `final` from version 3.0 onwards and have been marked as such in the current version.
In preparation for this change, make sure any of your classes extending them are refactored away from their inheritance.

### Invokable Factory Classes

Due to the upcoming update to `laminas/laminas-servicemanager 4` in version 3.0, all factory classes implementing `Laminas\ServiceManager\Factory\FactoryInterface` will be forced to be invokable classes:

- `ContainerAbstractServiceFactory`
- `SessionConfigFactory`
- `SessionManagerFactory`
- `StorageFactory`

The `createService()` method implemented in each of the factories is to be removed and has been marked as `deprecated` in the current version.

These factories will also be made `final` in version 3.0 and have been annotated as such in the current version.

### ValidatorInterface Changes

Starting from version 3.0, the `ValidatorInterface` will only specify a `validate` method, with mandatory
`Environment` objects representing the initial and current states to be used by the validators.

The new method signature is:

```php
public function validate(EnvironmentInterface $initial, EnvironmentInterface $current): void;
```

The following validator classes, all implementing `ValidatorInterface`, make use of the new method:

- `HttpUserAgent`
- `Id`
- `RemoteAddr`

If you have custom validators implementing `ValidatorInterface`, you will need to update them to work with
`EnvironmentInterface` objects.

As opposed to the old `isValid` usage, any failing `validate` call will throw the `SessionValidationFailedException`
added in version 3 instead of returning boolean values.

The following methods will be removed from the interface as well as from any implementing class:

- `isValid`
- `getData`
- `getName`

## Features That Will Be Removed

### `laminas/laminas-cache` Replacement

From version 3.0 onwards, `laminas-session` will remove the `laminas/laminas-cache`
dependency and replace it with `psr/simple-cache` to adhere to PSR-16.

The `Laminas\Session\SaveHandler\Cache` class will be updated to reflect this change.
Starting from version 3.0 the class will be made `final` and has been marked as such in the current version.
The following properties have been marked as deprecated in version 2.25 and will be removed in version 3.0:

- `sessionSavePath`
- `sessionName`

The following methods will also be removed and have been marked as deprecated in the current version:

- `setCacheStorage`
- `getCacheStorage`
- `getCacheStorge`

To ensure all session IDs used as PSR-16 cache keys are spec-compliant, version 3.0 will hash session IDs
internally before passing them to the cache. This means you can no longer look up session data in the cache
directly by raw session ID.

Two new exception classes will be introduced to wrap PSR-16 cache exceptions and rethrow them in
the package's own namespace:

- `Laminas\Session\Exception\SimpleCacheException`
- `Laminas\Session\Exception\SimpleCacheInvalidArgumentException`

As such, any try/catch blocks for exceptions thrown by the `Cache` save handler will need to be updated.

Going forward, you may move `laminas/laminas-cache` to your application's direct requirements to keep using it,
or find a PSR-16 implementation in the `psr/simple-cache-implementation` virtual repository or [Laminas Integrations Page](https://getlaminas.org/integrations/).

### `laminas/laminas-db` Removal

Starting from version 3.0 the `laminas/laminas-db` dependency will be removed.
The following classes are based on this package and have been marked as `deprecated` in the current version:

- `Laminas\Session\SaveHandler\DbTableGateway`
- `Laminas\Session\SaveHandler\DbTableGatewayOptions`

Version 3.0 will remove these classes without replacement - any custom code based on them will require you to implement
replacements yourself by creating a class that implements `Laminas\Session\SaveHandler\SaveHandlerInterface`
as per [the custom save handler documentation](../save-handler.md),
with an `options` class for it if the save handler is configurable.

### `laminas/laminas-http` Removal

The `laminas/laminas-http` dependency will be removed without replacement starting from version 3.0.
The role of getting the user's IP address will be moved to a public static `RemoteAddr::getIpAddress()` method.

Make sure to explicitly require this package if your custom code is making use of it.

### `laminas/laminas-stdlib` Removal

The `laminas/laminas-stdlib` dependency will be removed starting from version 3.0, to be replaced with native PHP alternatives
with no signature or behaviour changes.

Make sure to explicitly require this package if your custom code is making use of it.

### `laminas/laminas-eventmanager` Removal

The `laminas/laminas-eventmanager` dependency will be removed in version 3.0. It was previously used internally
by `SessionManager` to drive the validator chain, which has been replaced with a direct validation loop.

Make sure to explicitly require this package if your custom code is making use of it.

### `MongoDB` Removal

`Laminas\Session\SaveHandler\MongoDB` and its options class `Laminas\Session\SaveHandler\MongoDBOptions`
are now deprecated and will be removed without replacement in version 3.0.
As such, these classes are to be removed from your inheritance tree.

With this step, the `mongodb/mongodb` dependency will also be removed starting from version 3.0.

### `ValidatorChain` Removal

As of version 3.0, the `ValidatorChain` class will be removed, as building the validator list is being moved
to the `SessionManager` itself. Remove any usage of `ValidatorChain` from your application.

### Module Removal

As usage in `laminas-mvc` applications will no longer be supported, the `Module` class will be removed
and has been marked as deprecated in the current version.

### Legacy Deprecated Classes Removal

The following classes currently marked as deprecated and left for backward compatibility will be removed beginning with version 3.0:

- `autoload-dev/ReturnTypeWillChange`
- `AbstractValidatorChain`
- `AbstractValidatorChainEM3`
- `ValidatorChainTrait`
- `src/compatibility/autoload.php`

Their usage is to be removed from your application in preparation for the next version.

### ValidatorInterface Deprecation

Starting from version 3.0 the following methods of `ValidatorInterface` will be removed:

- `getData`
- `getName`

These methods have been annotated as `deprecated` in the current version.

### Csrf Deprecations

The `Csrf` validator will be updated in version 3.0. All required configuration will have to be passed via the
constructor options array - the following public getters and setters will be removed:

- `setName`
- `getName`
- `setSession`
- `getSession`
- `setSalt`
- `getSalt`
- `getHash`
- `setTimeout`
- `getTimeout`

In the current version, all these methods have been marked as `deprecated`.

### HttpUserAgent Deprecations

With version 3.0, the `data` property will be removed alongside its getter:

- `getData`

The constructor will also be removed. They have been marked as `deprecated` in the current version.

### Id Deprecations

With version 3.0, the `id` property will be removed alongside its getter:

- `getData`

The constructor will also be removed. In the current version, they have both been annotated as `deprecated`.

### RemoteAddr Deprecations

The following properties will be removed in version 3.0:

- `data`
- `useProxy`
- `trustedProxies`
- `proxyHeader`

The following getters and setters will also be removed:

- `getData`
- `setUseProxy`
- `getUseProxy`
- `setTrustedProxies`
- `setProxyHeader`

The constructor's signature will change to only accept an associative array of options. Note that the old
static properties (`$useProxy`, `$trustedProxies`, `$proxyHeader`) will be replaced by the constructor
options array.

These properties and methods have been marked as `deprecated` starting from version 2.25.
