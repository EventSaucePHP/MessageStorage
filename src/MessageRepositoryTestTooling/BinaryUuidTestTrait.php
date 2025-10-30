<?php

declare(strict_types=1);

namespace EventSauce\MessageRepository\TestTooling;

trait BinaryUuidTestTrait
{
    /**
     * @test
     */
    public function it_uses_correct_parameter_types_for_binary_fields(): void
    {
        if (version_compare(PHP_VERSION, '8.4', '<')) {
            self::markTestSkipped('PHP version needs to be >=8.4 to have PDO use binary hints');
        }

        $repository = $this->messageRepository();
        $message = $this->createMessage('payload');

        $repository->persist($message);
        $this->assertWarnings('persist()');

        $repository->retrieveAll($message->aggregateRootId());
        $this->assertWarnings('retrieveAll()');

        $repository->retrieveAllAfterVersion($message->aggregateRootId(), 1);
        $this->assertWarnings('retrieveAllAfterVersion()');
    }

    private function assertWarnings(string $scenario): void
    {
        $warnings = $this->connection->executeQuery('SHOW WARNINGS')->fetchAllAssociative();
        self::assertSame([], $warnings, 'Binary uuids were not properly passed during ' . $scenario);
    }
}
