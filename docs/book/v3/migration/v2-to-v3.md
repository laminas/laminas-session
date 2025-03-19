# Migration from Version 2 to 3

`laminas-session` introduces a number of backwards incompatible changes that might affect your application.
This document details those changes, and provides suggestions on how to update your application to work with version 3.

## Removed Features

### `MongoDB` Removal

MongoDB support has been completely removed in version 3.0, notably the following classes no longer exist:

- `Laminas\Session\SaveHandler\MongoDB`
- `Laminas\Session\SaveHandler\MongoDBOptions`

If you require MongoDB support in your application, you will need to implement that support yourself
by creating a class that implements `Laminas\Session\SaveHandler\SaveHandlerInterface` as per [the custom save handler documentation](../save-handler.md).
