<?php

namespace EventSauce\MessageOutbox\TestTooling;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\MessageOutbox\OutboxRepository;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

abstract class TransactionalMessageRepositoryTestCase extends TestCase
{
    protected string $repositoryTable = '';
    protected string $outboxTable = '';
    protected AggregateRootId $aggregateRootId;
    protected array $eventIds = [];
    protected int $idCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repositoryTable = 'domain_messages_uuid';
        $this->outboxTable = 'outbox_messages';
        $this->aggregateRootId = $this->aggregateRootId();
        $this->idCounter = 0;
        $this->eventIds = [
            'fd574b80-079a-4bc7-8a39-062ba0581668',
            '2aea7313-bc07-498d-be82-ce0ffe0d6727',
            'a1534b26-8956-41db-b2a1-e7d82e09be53',
            '2bbd076a-5134-4dbc-b846-59c55be1ffa5',
            'c2f82a82-8a20-4104-90b7-cf352efb2a11',
        ];
    }

    abstract protected function messageRepository(): MessageRepository;
    abstract protected function outboxRepository(): OutboxRepository;
    abstract protected function transactionalRepository(): MessageRepository;
    abstract protected function aggregateRootId(): AggregateRootId;

    /**
     * @test
     */
    public function messages_are_persisted_in_both_repositories(): void
    {
        $messageRepository = $this->messageRepository();
        $outboxRepository = $this->outboxRepository();
        $transactionalRepository = $this->transactionalRepository();
        $message1 = $this->createMessage('one', 1);
        $message2 = $this->createMessage('two', 2);
        $message3 = $this->createMessage('three', 3);
        $message4 = $this->createMessage('four', 4);

        $transactionalRepository->persist($message1, $message2, $message3, $message4);

        self::assertEquals(4, $outboxRepository->numberOfMessages());
        self::assertEquals(4, $outboxRepository->numberOfPendingMessages());
        /** @var list<Message> $messages */
        $messages = iterator_to_array($messageRepository->retrieveAll($this->aggregateRootId));
        self::assertCount(4, $messages);
        self::assertEquals($this->eventIds[0], $messages[0]->header(Header::EVENT_ID));
        self::assertEquals($this->eventIds[2], $messages[2]->header(Header::EVENT_ID));
    }

    /**
     * @test
     */
    public function messages_are_not_stored_in_the_repository_when_the_outbox_fails(): void
    {
        $this->outboxTable = 'invalid';
        $messageRepository = $this->messageRepository();
        $transactionalRepository = $this->transactionalRepository();

        try {
            $transactionalRepository->persist($this->createMessage('one', 1));
            // @codeCoverageIgnoreStart
            $this->assertFalse(true, 'we failed to raise an exception');
            // @codeCoverageIgnoreEnd
        } catch (UnableToPersistMessages) {
        }

        $messages = iterator_to_array($messageRepository->retrieveAll($this->aggregateRootId));
        self::assertCount(0, $messages);
    }

    /**
     * @test
     */
    public function messages_are_not_stored_in_the_outbox_when_the_repository_fails(): void
    {
        $this->repositoryTable = 'invalid';
        $outboxRepository = $this->outboxRepository();
        $transactionalRepository = $this->transactionalRepository();

        try {
            $transactionalRepository->persist($this->createMessage('one', 1));
            // @codeCoverageIgnoreStart
            $this->assertFalse(true, 'we failed to raise an exception');
            // @codeCoverageIgnoreEnd
        } catch (UnableToPersistMessages) {
        }

        self::assertEquals(0, $outboxRepository->numberOfMessages());
    }

    /**
     * @test
     */
    public function messages_are_persisted_with_consistent_ids(): void
    {
        $messageRepository = $this->messageRepository();
        $outboxRepository = $this->outboxRepository();
        $transactionalRepository = $this->transactionalRepository();
        $message1 = $this->createMessageWithoutId('one', 1);
        $message2 = $this->createMessageWithoutId('two', 2);
        $message3 = $this->createMessageWithoutId('three', 3);
        $message4 = $this->createMessageWithoutId('four', 4);

        $transactionalRepository->persist($message1, $message2, $message3, $message4);

        $messageIds = array_map(
            static fn (Message $message) => $message->header(Header::EVENT_ID),
            iterator_to_array($messageRepository->retrieveAll($this->aggregateRootId)),
        );

        $messageIdsInOutbox = array_map(
            static fn (Message $message) => $message->header(Header::EVENT_ID),
            iterator_to_array($outboxRepository->retrieveBatch(10)),
        );

        self::assertSame($messageIds, $messageIdsInOutbox);
    }

    /**
     * @test
     */
    public function persisted_messages_can_be_retrieved(): void
    {
        $transactionalRepository = $this->transactionalRepository();
        $message1 = $this->createMessage('one', 1);
        $message2 = $this->createMessage('two', 2);
        $message3 = $this->createMessage('three', 3);
        $message4 = $this->createMessage('four', 4);

        $transactionalRepository->persist($message1, $message2, $message3, $message4);
        $messages = iterator_to_array($transactionalRepository->retrieveAll($this->aggregateRootId));
        $messagesAfterVersion = iterator_to_array($transactionalRepository->retrieveAllAfterVersion($this->aggregateRootId, 2));

        self::assertCount(4, $messages);
        self::assertCount(2, $messagesAfterVersion);
    }

    protected function createMessage(string $value, int $version): Message
    {
        $message = (new Message(new DummyEvent($value)))
            ->withHeader(Header::AGGREGATE_ROOT_ID, $this->aggregateRootId)
            ->withHeader(Header::AGGREGATE_ROOT_VERSION, $version)
            ->withHeader(header::EVENT_ID, $this->eventIds[$this->idCounter]);

        $this->idCounter++;

        return (new DefaultHeadersDecorator())->decorate($message);
    }

    protected function createMessageWithoutId(string $value, int $version): Message
    {
        $message = (new Message(new DummyEvent($value)))
            ->withHeader(Header::AGGREGATE_ROOT_ID, $this->aggregateRootId)
            ->withHeader(Header::AGGREGATE_ROOT_VERSION, $version);

        return (new DefaultHeadersDecorator())->decorate($message);
    }
}
