# Migration from Version 2 to 3

`laminas-session` introduces a number of backwards incompatible changes that might affect your application.
This document details those changes, and provides suggestions on how to update your application to work with version 3.

## Removed Features

### `MongoDB` Removal

`Laminas\Session\SaveHandler\MongoDB` and its options class `Laminas\Session\SaveHandler\MongoDBOptions`
are removed without replacement in version 3.0.
As such, these classes are to be removed from your inheritance tree.
With this step, the `mongodb/mongodb` dependency is also removed starting from this version.
