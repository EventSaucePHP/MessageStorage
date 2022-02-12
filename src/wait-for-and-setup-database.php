<?php

use EventSauce\BackOff\LinearBackOffStrategy;
use Illuminate\Database\Capsule\Manager;

/**
 * @codeCoverageIgnore
 */
include __DIR__ . '/../vendor/autoload.php';

$manager = new Manager;
$manager->addConnection(
    [
        'driver' => 'mysql',
        'host' => getenv('EVENTSAUCE_TESTING_MYSQL_HOST') ?: '127.0.0.1',
        'port' => getenv('EVENTSAUCE_TESTING_MYSQL_PORT') ?: '3306',
        'database' => 'outbox_messages',
        'username' => 'username',
        'password' => 'password',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ]
);

$tries = 0;
$backOff = new LinearBackOffStrategy(200000, 50);

while(true) {
    start:
    try {
        $tries++;
        $connection = $manager->getConnection();
        $connection->select('SHOW TABLES');
        fwrite(STDOUT, "DB connection established!\n");
        break;
    } catch (Throwable $exception) {
        fwrite(STDOUT, "Waiting for a DB connection...\n");
        $backOff->backOff($tries, $exception);
        goto start;
    }
}

$connection->getSchemaBuilder()->dropIfExists('outbox_messages');
$connection->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `outbox_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `consumed` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `payload` varchar(16001) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `is_consumed` (`consumed`, `id` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL
);

$connection->getSchemaBuilder()->dropIfExists('domain_messages_uuid');
$connection->statement(<<<SQL
CREATE TABLE IF NOT EXISTS `domain_messages_uuid` (
  `event_id` BINARY(16) NOT NULL,
  `aggregate_root_id` BINARY(16) NOT NULL,
  `version` int(20) unsigned NULL,
  `payload` varchar(16001) NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY (`aggregate_root_id`),
  KEY `reconstitution` (`aggregate_root_id`, `version` ASC)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB;
SQL
);

$connection->statement("DROP TABLE IF EXISTS legacy_domain_messages_uuid");
$connection->statement("
CREATE TABLE IF NOT EXISTS legacy_domain_messages_uuid (
    event_id VARCHAR(36) NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    aggregate_root_id VARCHAR(36) NOT NULL,
    aggregate_root_version MEDIUMINT(36) UNSIGNED NOT NULL,
    time_of_recording DATETIME(6) NOT NULL,
    payload JSON NOT NULL,
    INDEX aggregate_root_id (aggregate_root_id),
    UNIQUE KEY unique_id_and_version (aggregate_root_id, aggregate_root_version ASC)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci ENGINE=InnoDB
");
