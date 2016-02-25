# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.6.2 - 2016-02-25

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#32](https://github.com/zendframework/zend-session/pull/32) provides a better
  polfill for the `ValidatorChain` to ensure it can be represented in
  auto-generated classmaps (e.g., via `composer dump-autoload --optimize` and/or
  `composer dump-autoload --classmap-authoritative`).

## 2.6.1 - 2016-02-23

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#29](https://github.com/zendframework/zend-session/pull/29) extracts the
  constructor defined in `Zend\Session\Validator\ValidatorChainTrait` and pushes
  it into each of the `ValidatorChainEM2` and `ValidatorChainEM3`
  implementations, to prevent colliding constructor definitions due to
  inheritance + trait usage.

## 2.6.0 - 2016-02-23

### Added

- [#29](https://github.com/zendframework/zend-session/pull/29) adds two new
  classes: `Zend\Session\Validator\ValidatorChainEM2` and `ValidatorChainEM3`.
  Due to differences in the `EventManagerInterface::attach()` method between
  zend-eventmanager v2 and v3, and the fact that `ValidatorChain` overrides that
  method, we now need an implementation targeting each major version. To provide
  a consistent use case, we use a polyfill that aliases the appropriate version
  to the `Zend\Session\ValidatorChain` class.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#29](https://github.com/zendframework/zend-session/pull/29) updates the code
  to be forwards compatible with the v3 releases of zend-eventmanager and
  zend-servicemanager.
- [#7](https://github.com/zendframework/zend-session/pull/7) Mongo save handler
  was using sprintf formatting without sprintf.

## 2.5.2 - 2015-07-29

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#3](https://github.com/zendframework/zend-session/pull/3) Utilize
  SaveHandlerInterface vs. our own.

- [#2](https://github.com/zendframework/zend-session/pull/2) detect session
  exists by use of *PHP_SESSION_ACTIVE*
