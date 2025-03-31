# Preparing for Version 3

Version 3 will introduce a number of backwards incompatible changes.
This document is intended to help you prepare for these changes.

## Removed Features

### `laminas/laminas-cache` Removal

From version 3.0 onwards, `laminas-session` will remove the `laminas/laminas-cache`
dependency and replace it with `psr/simple-cache` to adhere to PSR-16.

The `Laminas\Session\SaveHandler\Cache.php` class will be updated to reflect this change.
Starting from version 3.0 the class will be made `final` and has been marked as such in the current version.
The following properties have been marked as deprecated and will be removed in version 3.0:

- `sessionSavePath`
- `sessionName`

The following methods will also be removed and have been marked as deprecated in the current version:

- `setCacheStorage`
- `getCacheStorage`
- `getCacheStorge`

Going forward, you may move `laminas/laminas-cache` to your application's direct requirements to keep using it,
or find a PSR-16 implementation in the `psr/simple-cache-implementation` virtual repository.

### `laminas/laminas-db` Removal

Starting from version 3.0 the `laminas/laminas-db` dependency will be removed.
The following classes are based on this package and have been marked as `deprecated` in the current version:

- `Laminas\Session\SaveHandler\DbTableGateway`
- `Laminas\Session\SaveHandler\DbTableGatewayOptions`

Version 3.0 will remove these classes without replacement - any custom code based on them will require you to implement
replacements yourself by creating a class that implements `Laminas\Session\SaveHandler\SaveHandlerInterface`
as per [the custom save handler documentation](../save-handler.md),
with an `options` class for it if the save handler is configurable.

### `MongoDB` Removal

`Laminas\Session\SaveHandler\MongoDB` and its options class `Laminas\Session\SaveHandler\MongoDBOptions`
are now deprecated and will be removed without replacement in version 3.0.
As such, these classes are to be removed from your inheritance tree.
With this step, the `mongodb/mongodb` dependency will also be removed starting from version 3.0.
