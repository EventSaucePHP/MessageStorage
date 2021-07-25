# EventSauce Message Storage Monorepo

This repository contains multiple packages that all implement
a part of EventSauce's storage related interfaces.

## Message Repositories

Message repositories are used to store messages in for
aggregate root reconstitution.

[View the docs](#pending)

## Message Outboxes

A message outbox can be used to dispatching of messages
via a database buffer table. Doing so allows you to ensure
message persistence and dispatching happens in a single
transaction.

[View the docs](https://eventsauce.io/docs/message-outbox/)
