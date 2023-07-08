# Changelog

## 1.1.0 - 2023-07-08

### Added

- [Outbox] Added relay mechanism based on a dispatcher that supports batched relaying (by @lcobucci)
- [Outbox] Added relay mechanism based on a consumer for single message relaying (by @lcobucci)

### Deprecated

- [Outbox] The `OutboxRelay` was renamed to `RelayMessagesThroughConsumer`
  but available under a backward compatibility extension/alias. (by @lcobucci)
 
