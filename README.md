# EventSauce Message Storage Monorepo

This repository contains multiple packages that all implement
a part of EventSauce's storage related interfaces.

## Message Repositories

Message repositories are used to store messages in for
aggregate root reconstitution.

Docs coming soon...

## Message Outboxes

A message outbox can be used to dispatching of messages
via a database buffer table. Doing so allows you to ensure
message persistence and dispatching happens in a single
transaction.

[View the docs](https://eventsauce.io/docs/message-outbox/)

## Testing

### Dependencies

Testing these packages requires accessing a MySQL 8
database.

By default, the tests assume MySQL is running on
127.0.0.1 and listening on port 3306.

To run tests with a different host or port, the
following environment variables can be set:

 * **EVENTSAUCE_TESTING_MYSQL_HOST**: Set to the IP address
   of the host running MySQL.
 * **EVENTSAUCE_TESTING_MYSQL_PORT**: Set to the port that
   MySQL is listening on.

### Running

#### Schema

All tests require the expected schema to be in place.
This schema can be created by running:

```shell
php src/wait-for-and-setup-database.php
```

#### Test all implementations except Doctrine 2

The test suite includes tests for multiple implementations.

The test suite includes tests for two major versions of
Doctrine (2 and 3) that are not compatible with each other
and cannot be installed at the same time.

This means we can test all implementations together except
for the Doctrine 2 implementation.

Running all tests except for the Doctrine 2 tests can
be accomplished by excluding tests in the **doctrine2**
group.

```shell
./vendor/bin/phpunit --verbose --exclude-group=doctrine2
```

#### Doctrine 2 tests

In order to test the Doctrine 2 implementation, the
dependencies for the project must first be updated to
use Doctrine 2 instead of the Doctrine 3 default.

```shell
composer require doctrine/dbal:^2.12
```

This will replace Doctrine 3 with Doctrine 2. **This
means the Doctrine 3 tests will no longer run
correctly.**

After the Composer dependencies are downgraded to
Doctrine 2 the Doctrine 2 implementation can be
tested. Since the rest of the implementations
are already tested using the first method,
testing the Doctrine 2 implementation can
be accomplished by only running tests in
the **doctrine2** group.

```shell
./vendor/bin/phpunit --verbose --group=doctrine2
```

**Take care to not include the changes to
`composer.json` in any PR you intend to share.**

### Docker Compose

This package ships with a Docker Compose file in the
project's root directory. This can be used to create
a MySQL 8 server that can be used for testing this
package.

Run:

```yaml
docker-compose up
```

Once running, the testing commands can be run.

It also has some helper services to run tests on different php versions.
For example to emulate what happens in the Github Workflow:

```
docker compose run --rm php80 composer require 'doctrine/dbal:^2.12' 'carbonphp/carbon-doctrine-types:*' -w --prefer-stable
docker compose run --rm php80 ./vendor/bin/phpunit --group=doctrine2
```
