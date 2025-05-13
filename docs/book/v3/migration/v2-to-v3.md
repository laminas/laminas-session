# Migration from Version 2 to 3

`laminas-session` introduces a number of backwards incompatible changes that might affect your application.
This document details those changes, and provides suggestions on how to update your application to work with version 3.

## Removed Features

### `laminas/laminas-db` Removal

Starting from version 3.0 the `laminas/laminas-db` dependency has been removed.
In consequence the following classes, based on this package, have also been removed:

- `Laminas\Session\SaveHandler\DbTableGateway`
- `Laminas\Session\SaveHandler\DbTableGatewayOptions`

Any custom code based on them will require you to implement
replacements yourself by creating classes that implement `Laminas\Session\SaveHandler\SaveHandlerInterface`
as per [the custom save handler documentation](../save-handler.md),
with `options` classes for them if the save handlers are configurable.

Alternatively, [axleus/laminas-db](https://github.com/axleus/laminas-db) is an up to date,
actively maintained fork of `laminas/laminas-db` which plans to adopt the save handler.

### `MongoDB` Removal

MongoDB support has been completely removed in version 3.0, notably the following classes no longer exist:

- `Laminas\Session\SaveHandler\MongoDB`
- `Laminas\Session\SaveHandler\MongoDBOptions`

If you require MongoDB support in your application, you will need to implement that support yourself
by creating a class that implements `Laminas\Session\SaveHandler\SaveHandlerInterface` as per [the custom save handler documentation](../save-handler.md).

## Changed Features

### `sessionExists()` Method Changes

The implementation of the `SessionManager::sessionExists()` method has been simplified and no longer uses the PHP constant `SID` because this is [deprecated since PHP 8.4](https://wiki.php.net/rfc/deprecations_php_8_4#constant_sid).

> NOTE: **Logical change**
> Due to this implementation change, `sessionExists()` will now return `false` after `session_close()` has been called, whereas in version 2 it would return `true`.
