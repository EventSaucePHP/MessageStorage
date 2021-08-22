# Doctrine Message Repository

`MessageRepository` implementation that uses Doctrine DBAL for event persistence.

## Usage

```php
use EventSauce\MessageRepository\DoctrineMessageRepository\DoctrineUuidV4MessageRepository;
use EventSauce\MessageRepository\TableSchema\DefaultTableSchema;
use EventSauce\UuidEncoding\BinaryUuidEncoder;

$messageRepository = new DoctrineUuidV4MessageRepository(
    connection: $doctrineDbalConnection,
    tableName: $tableName,
    serializer: $eventSauceMessageSerializer,
    tableSchema: new DefaultTableSchema(), // optional
    uuidEncoder: new BinaryUuidEncoder(), // optional
);
```

## Table Schema

`TableSchema` allows customizing the table and column names used when performing
database operations, as well as allowing for additional headers to be added.

### Default Table Schema

The default implementation `DefaultTableSchema` uses the following column names:

- `event_id` primary key (text/UUID)
- `aggregate_root_id` aggregate root ID (text/UUID)
- `version` aggregate root version (int)
- `payload` encoded event payload (text/JSON)

### Legacy Table Schema

For users upgrading from EventSauce pre-1.0, there is a `LegacyTableSchema`:

- `event_id` primary key (text/UUID)
- `event_type` the serialized event name (text)
- `aggregate_root_id` aggregate root ID (text/UUID)
- `aggregate_root_version` aggregate root version (int)
- `time_of_recording` when the event was written (timestamp)
- `payload` encoded event payload (text/JSON)

### Custom Implementations

Custom implementations of `TableSchema` can use the `additionalColumns` method to
write other `Header` values to columns, which can be useful for indexing.

## UUID Encoder

`UuidEncoder` allows customizing how the UUIDs used for the event ID and the
aggregate root ID are converted to string when written to the database.

### Binary UUID Encoder

`UuidBinaryEncoder` encodes the UUID using `$uuid->getBytes()` to generate a binary
text version of the UUID, which should be used when the database does not have a
native `uuid` type.

### String UUID Encoder

`UuidStringEncoder` encodes the UUID using `$uuid->toString()` to generate a plain
text version of the UUID, which should be used when the database has a `uuid` type.

### Custom Implementations

Custom implementations of `UuidEncoder` can be used to optimize UUID storage as needed.
