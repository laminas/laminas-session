# Preparing for Version 3

Version 3 will introduce a number of backward incompatible changes.
This document is intended to help you prepare for these changes.

## Upcoming Behaviour & Signature Changes

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

### ValidatorInterface Defining Constructor

Starting from version 3.0, the `ValidatorInterface` will define a new specific signature for implementing classes' constructors.
In consequence, any custom validators implementing this interface will have to be updated with the following `__construct` signature:

```php
public function __construct(Environment $initial, Environment $current, array $options = []);
```

The following validator classes, all implementing `ValidatorInterface`, will use the shown constructor signature:

- `HttpUserAgent`
- `Id`
- `RemoteAddr`

## Features That Will Be Removed

### `laminas/laminas-cache` Replacement

From version 3.0 onwards, `laminas-session` will remove the `laminas/laminas-cache`
dependency and replace it with `psr/simple-cache` to adhere to PSR-16.

The `Laminas\Session\SaveHandler\Cache.php` class will be updated to reflect this change.
Starting from version 3.0 the class will be made `final` and has been marked as such in the current version.
The following properties have been marked as deprecated in version 2.25 and will be removed in version 3.0:

- `sessionSavePath`
- `sessionName`

The following methods will also be removed and have been marked as deprecated in the current version:

- `setCacheStorage`
- `getCacheStorage`
- `getCacheStorge`

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
The role of getting the user's IP address will be moved to a public `RemoteAddr::getIpAddress()` method that will be statically accessible if needed.

Make sure to explicitly require this package if your custom code is making use of it.

### `laminas/laminas-stdlib` Removal

The `laminas/laminas-stdlib` dependency will be removed starting from version 3.0, to be replaced with native PHP alternatives
with no signature or behaviour changes.

Make sure to explicitly require this package if your custom code is making use of it.

### `MongoDB` Removal

`Laminas\Session\SaveHandler\MongoDB` and its options class `Laminas\Session\SaveHandler\MongoDBOptions`
are now deprecated and will be removed without replacement in version 3.0.
As such, these classes are to be removed from your inheritance tree.

With this step, the `mongodb/mongodb` dependency will also be removed starting from version 3.0.

### Legacy Deprecated Classes Removal

The following classes currently marked as deprecated and left for backward compatibility will be removed beginning with version 3.0:

- `autoload-dev/ReturnTypeWillChange`
- `AbstractValidatorChain`
- `AbstractValidatorChainEM3`
- `ValidatorChainTrait`
- `src/compatibility/autoload.php`

Their usage is to be removed from your application in preparation for the next version.

### ValidatorInterface Deprecation

Starting from version 3.0 the following method of `ValidatorInterface` will be removed:

- `getData`

The method has been annotated as `deprecated` in version 2.25.

### Csrf Deprecations

The `Csrf` validator will be updated in version 3.0, and the following getters and setters will be removed:

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

They have been marked as `deprecated` in the current version.

### Id Deprecations

With version 3.0, the `id` property will be removed alongside its getter:

- `getData`

In the current version, they have both been as annotated as `deprecated`.

### RemoteAddr Deprecations

The `data` property will be removed in version 3.0, as well as the following getters and setters:

- `getData`
- `setUseProxy`
- `getUseProxy`
- `setTrustedProxies`
- `setProxyHeader`

The `data` property and the listed methods have been marked as `deprecated` starting from version 2.25.
