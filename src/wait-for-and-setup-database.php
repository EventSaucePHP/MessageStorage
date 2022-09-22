<?php

use EventSauce\BackOff\LinearBackOffStrategy;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;

/**
 * @codeCoverageIgnore
 */
include __DIR__ . '/../vendor/autoload.php';

function setup_database(string $driver, string $host, string $port): void
{
    $manager = new Manager;
    $manager->addConnection(
        [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => 'outbox_messages',
            'username' => 'username',
            'password' => 'password',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
        ]
    );

    $tries = 0;
    $backOff = new LinearBackOffStrategy(200000, 50);

    while (true) {
        start:
        try {
            $tries++;
            $connection = $manager->getConnection();
            $connection->select('SELECT 1');
            fwrite(STDOUT, "DB connection established!\n");
            break;
        } catch (Throwable $exception) {
            fwrite(STDOUT, "Waiting for a DB connection...\n" . $exception->getMessage());
            $backOff->backOff($tries, $exception);
            goto start;
        }
    }

    $schema = $connection->getSchemaBuilder();
    $schema->dropIfExists('outbox_messages');
    $schema->create('outbox_messages', function(Blueprint $table) {
        $table->id();
        $table->boolean('consumed')->default(false);
        $table->string('payload', 16001);
        $table->index(['consumed', 'id'], 'is_consumed');
    });

    $connection->getSchemaBuilder()->dropIfExists('domain_messages_uuid');
    $schema = $connection->getSchemaBuilder();
    $schema->create('domain_messages_uuid', function(Blueprint $table) {
        $table->id();
        $table->binary('event_id');
        $table->binary('aggregate_root_id')->nullable();
        $table->integer('version')->default(0);
        $table->string('payload', 16001);
    });

    $connection->statement("DROP TABLE IF EXISTS legacy_domain_messages_uuid");

    $schema->create('legacy_domain_messages_uuid', function(Blueprint $table) {
        $table->id();
        $table->uuid('event_id');
        $table->string('event_type', 100);
        $table->dateTime('time_of_recording', 6);
        $table->uuid('aggregate_root_id')->nullable();
        $table->integer('aggregate_root_version')->default(0);
        $table->json('payload');
        $table->unique(['aggregate_root_id', 'aggregate_root_version'], 'version_unique');
    });
}

setup_database(
    'mysql',
    getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1',
    getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306',
);
setup_database(
    'pgsql',
    getenv('EVENTSAUCE_TESTING_PGSQL_HOST') ?: '127.0.0.1',
    getenv('EVENTSAUCE_TESTING_PGSQL_PORT') ?: '5432',
);
