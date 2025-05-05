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

### `laminas/laminas-servicemanager` Removal

Starting from version 3.0, the `laminas/laminas-servicemanager` dependency has been removed. The factory classes now work with any PSR-11 compatible container implementation. The following changes were made:

- `Laminas\Session\Service\ContainerAbstractServiceFactory` has been removed
- All exceptions that were previously `Laminas\ServiceManager\Exception\ServiceNotCreatedException` are now standard `\RuntimeException`s

If your application code depends on ServiceManager-specific behavior from these factories, you will need to update your code to:

- Handle standard RuntimeExceptions instead of ServiceManager exceptions

#### `ContainerAbstractServiceFactory` Replacement

The `Laminas\Session\Service\ContainerAbstractServiceFactory` was responsible for dynamically creating session container instances based on configuration. Since this has been removed, you'll need to create your own implementation if you were using this functionality.

**Before:**

```php
$serviceManagerConfig = [
    'session_containers' => [
        'CustomContainerName1',
    ]
];
$serviceManager->get('CustomContainerName1'); // Returns a \Laminas\Session\Container with the CustomContainerName1 Containername
```

**After:**

```php
$serviceManagerConfig = [
    'factories' => [
        'CustomContainerName1' => fn (\Psr\Container\ContainerInterface $container): \Laminas\Session\Container => 
            new \Laminas\Session\Container(
                'CustomContainerName1',
                 $container->get(ManagerInterface::class)
            );
    ]
];
$serviceManager->get('CustomContainerName1'); // Returns a \Laminas\Session\Container with the CustomContainerName1 Containername
```
