<?php

namespace EventSauce\MessageOutbox\TestTooling;

use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\MessageOutbox\OutboxRepository;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

abstract class OutboxRepositoryTestCase extends TestCase
{
    /**
     * @test
     */
    public function persisted_messages_can_be_retrieved(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');

        // Act
        $repository->persist($message1, $message2, $message3);
        /** @var list<Message> $messages */
        $messages = iterator_to_array($repository->retrieveBatch(10));

        // Assert
        $this->assertCount(3, $messages);
        $this->assertEquals($message1->event(), $messages[0]->event());
        $this->assertEquals($message2->event(), $messages[1]->event());
        $this->assertEquals($message3->event(), $messages[2]->event());
    }

    /**
     * @test
     */
    public function messages_can_be_marked_consumed(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');

        // Act
        $repository->persist($message1, $message2, $message3);
        /** @var list<Message> $messages */
        $messages = iterator_to_array($repository->retrieveBatch(2));
        $repository->markConsumed(...$messages);
        /** @var list<Message> $messages */
        $messages = iterator_to_array($repository->retrieveBatch(10));

        $this->assertCount(1, $messages);
        $this->assertEquals($message3->event(), $messages[0]->event());
    }

    /**
     * @test
     */
    public function it_exposes_counts_of_messages(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();
        $messages = [];

        for ($i = 1; $i <= 10; $i++) {
            $messages[] = $this->createMessage("Message {$i}");
        }

        $repository->persist(...$messages);
        $messages = iterator_to_array($repository->retrieveBatch(7));
        $repository->markConsumed(...$messages);

        // Act
        $numberOfConsumedMessages = $repository->numberOfConsumedMessages();
        $numberOfPendingMessages = $repository->numberOfPendingMessages();
        $numberOfMessages = $repository->numberOfMessages();

        // Assert
        self::assertEquals(7, $numberOfConsumedMessages);
        self::assertEquals(3, $numberOfPendingMessages);
        self::assertEquals(10, $numberOfMessages);
    }

    /**
     * @test
     */
    public function cleaning_up_consumed_messages(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();
        $messages = [];

        for ($i = 1; $i <= 10; $i++) {
            $messages[] = $this->createMessage("Message {$i}");
        }

        $repository->persist(...$messages);
        $messages = iterator_to_array($repository->retrieveBatch(7));
        $repository->markConsumed(...$messages);
        $repository->cleanupConsumedMessages(5);

        // Act
        $numberOfConsumedMessages = $repository->numberOfConsumedMessages();
        $numberOfPendingMessages = $repository->numberOfPendingMessages();
        $numberOfMessages = $repository->numberOfMessages();

        // Assert
        self::assertEquals(2, $numberOfConsumedMessages);
        self::assertEquals(3, $numberOfPendingMessages);
        self::assertEquals(5, $numberOfMessages);
    }

    /**
     * @test
     */
    public function persisting_no_messages(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();

        // Act
        $repository->persist();

        // Assert
        self::assertEquals(0, $repository->numberOfMessages());
    }

    /**
     * @test
     */
    public function marking_no_messages_as_consumed(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();
        $repository->persist($this->createMessage('one'));

        // Act
        $repository->markConsumed();

        self::assertEquals(1, $repository->numberOfMessages());
        self::assertEquals(1, $repository->numberOfPendingMessages());
        self::assertEquals(0, $repository->numberOfConsumedMessages());
    }



    /**
     * @test
     */
    public function deleting_no_messages(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();
        $repository->persist($this->createMessage('one'));

        // Act
        $repository->deleteMessages();

        self::assertEquals(1, $repository->numberOfMessages());
        self::assertEquals(1, $repository->numberOfPendingMessages());
        self::assertEquals(0, $repository->numberOfConsumedMessages());
    }

    /**
     * @test
     */
    public function deleting_messages_from_the_outbox(): void
    {
        // Arrange
        $repository = $this->outboxMessageRepository();
        $repository->persist(
            $this->createMessage('one'),
            $this->createMessage('two'),
            $this->createMessage('three'),
            $this->createMessage('four'),
        );

        // Act
        $messages = iterator_to_array($repository->retrieveBatch(2));
        $repository->deleteMessages(...$messages);

        self::assertEquals(2, $repository->numberOfMessages());
        self::assertEquals(2, $repository->numberOfPendingMessages());
        self::assertEquals(0, $repository->numberOfConsumedMessages());
    }

    protected function createMessage(string $value): Message
    {
        $message = new Message(new DummyEvent($value));

        return (new DefaultHeadersDecorator())->decorate($message);
    }

    abstract protected function outboxMessageRepository(): OutboxRepository;
}
