# Changelog

## 1.4.0 - 2025-12-09

### Improvement

- Doctrine now passes UUIDs using binary type hinting to prevent errors in MySQL (@wjzijderveld)

## 1.3.0 - 2025-05-04

### Fixes

- In-memory storage yields expected amount of messages (#30)
- PHP 8.4 deprecations (#34)
- Laravel v12 support (#33)

## 1.2.1 - 2024-05-22

- Doctrine V4 support for subsplits (@wjzijderveld)

## 1.2.0 - 2024-04-07

### Added

- Doctrine V4 support (@wjzijderveld)
- Support Laravel 11 (@axlon)

### Fixed

- Support pagination when there are gaps in the IDs (@thomasschiet)  


## 1.1.0 - 2023-07-08

### Added

- [Outbox] Added relay mechanism based on a dispatcher that supports batched relaying (by @lcobucci)
- [Outbox] Added relay mechanism based on a consumer for single message relaying (by @lcobucci)

### Deprecated

- [Outbox] The `OutboxRelay` was renamed to `RelayMessagesThroughConsumer`
  but available under a backward compatibility extension/alias. (by @lcobucci)
 
