<?php

namespace EventSauce\MessageOutbox;

use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageConsumer;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

class OutboxMessageRelayTest extends TestCase
{
    /**
     * @test
     */
    public function message_from_the_outbox_can_be_relayed(): void
    {
        // Arrange
        $repository = new InMemoryOutboxMessageRepository();
        $consumer = new class() implements MessageConsumer {
            /** @var list<Message> */
            public array $messages = [];
            public function handle(Message $message): void
            {
                $this->messages[] = $message;
            }
        };
        $relay = new OutboxMessageRelay($repository, $consumer);
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');
        $repository->persist($message1, $message2, $message3);

        // Act
        $publishedCount = $relay->publishBatch(10);

        // Assert
        $this->assertEquals(3, $publishedCount);
        $this->assertCount(3, $consumer->messages);
        $this->assertEquals($message1->event(), $consumer->messages[0]->event());
        $this->assertEquals($message2->event(), $consumer->messages[1]->event());
        $this->assertEquals($message3->event(), $consumer->messages[2]->event());
    }

    /**
     * @test
     */
    public function messages_can_be_committed_in_batches(): void
    {
        $repository = new class() extends InMemoryOutboxMessageRepository {
            public int $commitCount = 0;
            public function markConsumed(Message ...$messages): void
            {
                $this->commitCount++;
                parent::markConsumed(...$messages);
            }
        };
        $consumer = new class() implements MessageConsumer {
            public int $messageCount = 0;
            public function handle(Message $message): void
            {
                $this->messageCount++;
            }
        };
        $relay = new OutboxMessageRelay($repository, $consumer);
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');
        $message4 = $this->createMessage('four');
        $repository->persist($message1, $message2, $message3, $message4);

        // Act
        $relay->publishBatch(100, 3);
        $messages = iterator_to_array($repository->retrieveBatch(10));

        // Assert
        self::assertEquals(4, $consumer->messageCount);
        self::assertEquals(2, $repository->commitCount);
        self::assertCount(0, $messages);
    }

    private function createMessage(string $value): Message
    {
        $message = new Message(new DummyEvent($value));

        return (new DefaultHeadersDecorator())->decorate($message);
    }
}
