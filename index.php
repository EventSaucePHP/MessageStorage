<?php

use Doctrine\DBAL\DriverManager;
use EventSauce\EventSourcing\ConstructingAggregateRootRepository;
use EventSauce\EventSourcing\InMemoryMessageRepository;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\MessageOutbox\IlluminateOutboxMessageRepository;
use EventSauce\MessageOutbox\IlluminateTransactionalAggregateRootRepository;
use EventSauce\MessageOutbox\OutboxMessageDispatcher;
use EventSauce\MessageOutbox\OutboxMessageRelay;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;

include __DIR__ . '/vendor/autoload.php';

$manager = new Manager;
$manager->addConnection(
    [
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'outbox_messages',
        'username' => 'username',
        'password' => 'password',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ]
);

$connection = $manager->getConnection();
$connection->getSchemaBuilder()->dropIfExists('outbox_messages');
$connection->getSchemaBuilder()->create(
    'outbox_messages',
    function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('payload', 16001);
        $table->boolean('consumed')->default(false);
    }
);

$connection->table('outbox_messages')->truncate();

$dbal = DriverManager::getConnection(
    [
        'dbname' => 'outbox_messages',
        'user' => 'username',
        'password' => 'password',
        'host' => '127.0.0.1',
        'driver' => 'pdo_mysql',
    ]
);

var_dump($dbal->executeQuery('SHOW TABLES')->fetchFirstColumn());

$outboxRepository = new IlluminateOutboxMessageRepository(
    $connection, 'outbox_messages', new ConstructingMessageSerializer()
);
$innerAggregateRootRepository = new ConstructingAggregateRootRepository(
    AggregateRootClassName::class,
    new InMemoryMessageRepository(),
    new OutboxMessageDispatcher($outboxRepository),
);

$aggregateRootRepository = new IlluminateTransactionalAggregateRootRepository(
    $connection,
    $innerAggregateRootRepository
);

$aggregate = $aggregateRootRepository->retrieve(AggregateRootId::fromString('abcd'));
// interact with $aggregate
$aggregateRootRepository->persist($aggregate);

$relay = new OutboxMessageRelay($outboxRepository, new class implements MessageConsumer {
    public function handle(Message $message): void
    {
        // ignore
    }
});

while(true) {
    $published = $relay->publishBatch(100);

    if ($published === 0) {
        sleep(1);
    }
}


