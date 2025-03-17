# Preparing for Version 3

Version 3 will introduce a number of backwards incompatible changes.
This document is intended to help you prepare for these changes.

## Removed Features

### `MongoDB` Removal

`Laminas\Session\SaveHandler\MongoDB` and its options class `Laminas\Session\SaveHandler\MongoDBOptions`
are now deprecated and will be removed without replacement in version 3.0.
As such, these classes are to be removed from your inheritance tree.
With this step, the `mongodb/mongodb` dependency will also be removed starting from version 3.0.
