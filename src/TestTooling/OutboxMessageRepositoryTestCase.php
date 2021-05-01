<?php

namespace EventSauce\MessageOutbox\TestTooling;

use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\MessageOutbox\DummyEvent;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

abstract class OutboxMessageRepositoryTestCase extends TestCase
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

    private function createMessage(string $value): Message
    {
        $message = new Message(new DummyEvent($value));

        return (new DefaultHeadersDecorator())->decorate($message);
    }

    abstract protected function outboxMessageRepository(): OutboxMessageRepository;
}
