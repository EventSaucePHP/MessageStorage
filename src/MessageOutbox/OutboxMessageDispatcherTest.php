<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\UnableToDispatchMessages;
use PHPUnit\Framework\TestCase;

use RuntimeException;

use function iterator_to_array;

class OutboxMessageDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function dispatched_messages_end_up_in_the_outbox(): void
    {
        // Arrange
        $repository = new InMemoryOutboxRepository();
        $dispatcher = new OutboxMessageDispatcher($repository);
        $message1  = $this->createMessage('one');
        $message2  = $this->createMessage('two');

        // Act
        $dispatcher->dispatch($message1, $message2);

        // Assert
        /** @var list<Message> $messages */
        $messages = iterator_to_array($repository->retrieveBatch(10));
        $this->assertCount(2, $messages);
        $this->assertEquals($message1->event(), $messages[0]->event());
        $this->assertEquals($message2->event(), $messages[1]->event());
    }

    /**
     * @test
     */
    public function when_storing_fails_we_signal_we_are_unable_to_dispatch_the_messages(): void
    {
        // Arrange
        $repository = new class () extends InMemoryOutboxRepository{
            public function persist(Message ...$messages): void
            {
                throw new RuntimeException('Unable to store the messages');
            }
        };
        $dispatcher = new OutboxMessageDispatcher($repository);
        $message1  = $this->createMessage('one');
        $message2  = $this->createMessage('two');

        // Expect
        $this->expectException(UnableToDispatchMessages::class);

        // Act
        $dispatcher->dispatch($message1, $message2);
    }

    private function createMessage(string $value): Message
    {
        $message = new Message(new DummyEvent($value));

        return (new DefaultHeadersDecorator())->decorate($message);
    }
}
