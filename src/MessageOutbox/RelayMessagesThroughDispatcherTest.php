<?php

namespace EventSauce\MessageOutbox;

use EventSauce\BackOff\NoWaitingBackOffStrategy;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageDispatcher;
use LogicException;
use PHPUnit\Framework\TestCase;

use function array_push;
use function count;
use function iterator_to_array;

final class RelayMessagesThroughDispatcherTest extends TestCase
{
    /**
     * @test
     */
    public function message_from_the_outbox_can_be_relayed(): void
    {
        // Arrange
        $repository = new InMemoryOutboxRepository();
        $dispatcher = new class() implements MessageDispatcher {
            /** @var list<Message> */
            public array $messages = [];

            public function dispatch(Message ...$messages): void
            {
                array_push($this->messages, ...$messages);
            }
        };
        $relay = new RelayMessagesThroughDispatcher($repository, $dispatcher);
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');
        $repository->persist($message1, $message2, $message3);

        // Act
        $publishedCount = $relay->publishBatch(10);

        // Assert
        $this->assertEquals(3, $publishedCount);
        $this->assertCount(3, $dispatcher->messages);
        $this->assertEquals($message1->payload(), $dispatcher->messages[0]->payload());
        $this->assertEquals($message2->payload(), $dispatcher->messages[1]->payload());
        $this->assertEquals($message3->payload(), $dispatcher->messages[2]->payload());
    }

    /**
     * @test
     */
    public function messages_can_be_dispatched_and_committed_in_batches(): void
    {
        // Arrange
        $repository = new class() extends InMemoryOutboxRepository {
            public int $commitCount = 0;

            public function markConsumed(Message ...$messages): void
            {
                $this->commitCount++;
                parent::markConsumed(...$messages);
            }
        };
        $dispatcher = new class() implements MessageDispatcher {
            public int $messageCount = 0;
            public int $callsCount = 0;

            public function dispatch(Message ...$messages): void
            {
                $this->messageCount += count($messages);
                $this->callsCount++;
            }
        };
        $relay = new RelayMessagesThroughDispatcher($repository, $dispatcher);
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');
        $message4 = $this->createMessage('four');
        $repository->persist($message1, $message2, $message3, $message4);

        // Act
        $relay->publishBatch(100, 3);
        $messages = iterator_to_array($repository->retrieveBatch(10));

        // Assert
        self::assertEquals(4, $dispatcher->messageCount);
        self::assertEquals(2, $dispatcher->callsCount);
        self::assertEquals(2, $repository->commitCount);
        self::assertCount(0, $messages);
    }

    /**
     * @test
     */
    public function relaying_messages_is_tolerant_to_dispatcher_failures(): void
    {
        // Arrange
        $repository = new InMemoryOutboxRepository();
        $dispatcher = new class() implements MessageDispatcher {
            public int $callCount = 0;

            public int $handledCount = 0;

            public function dispatch(Message ...$messages): void
            {
                $this->callCount++;

                if ($this->callCount === 1) {
                    throw new LogicException('Oh no');
                }

                $this->handledCount++;
            }
        };
        $relay = new RelayMessagesThroughDispatcher($repository, $dispatcher, new NoWaitingBackOffStrategy(25));
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');
        $repository->persist($message1, $message2, $message3);

        // Act
        $relay->publishBatch(100);

        // Assert
        self::assertEquals(3, $dispatcher->handledCount);
        self::assertEquals(4, $dispatcher->callCount);
        self::assertEquals(3, $repository->numberOfMessages());
        self::assertEquals(3, $repository->numberOfConsumedMessages());
        self::assertEquals(0, $repository->numberOfPendingMessages());
    }

    /**
     * @test
     */
    public function using_a_delete_based_commit_strategy(): void
    {
        // Arrange
        $repository = new InMemoryOutboxRepository();
        $dispatcher = new class() implements MessageDispatcher {
            public int $callCount = 0;

            public function dispatch(Message ...$messages): void
            {
                $this->callCount++;
            }
        };
        $relay = new RelayMessagesThroughDispatcher(
            $repository, $dispatcher, new NoWaitingBackOffStrategy(25), new DeleteMessageOnCommit(),
        );
        $message1 = $this->createMessage('one');
        $message2 = $this->createMessage('two');
        $message3 = $this->createMessage('three');
        $repository->persist($message1, $message2, $message3);

        // Act
        $relay->publishBatch(100);

        // Assert
        self::assertEquals(3, $dispatcher->callCount);
        self::assertEquals(0, $repository->numberOfMessages());
        self::assertEquals(0, $repository->numberOfConsumedMessages());
        self::assertEquals(0, $repository->numberOfPendingMessages());
    }

    private function createMessage(string $value): Message
    {
        $message = new Message(new DummyEvent($value));

        return (new DefaultHeadersDecorator())->decorate($message);
    }
}
