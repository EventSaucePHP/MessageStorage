<?php

namespace EventSauce\MessageRepository\TestTooling;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use EventSauce\MessageOutbox\TestTooling\DummyEvent;
use PHPUnit\Framework\TestCase;

use function iterator_to_array;

abstract class MessageRepositoryTestCase extends TestCase
{
    protected string $tableName = '';
    protected AggregateRootId $aggregateRootId;

    abstract protected function messageRepository(): MessageRepository;
    abstract protected function aggregateRootId(): AggregateRootId;
    abstract protected function eventId(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aggregateRootId = $this->aggregateRootId();
        $this->tableName = 'domain_messages_uuid';
    }

    protected function createMessage(string $value): Message
    {
        return (new DefaultHeadersDecorator())
            ->decorate(new Message(new DummyEvent($value)))
            ->withHeader(Header::AGGREGATE_ROOT_ID, $this->aggregateRootId);
    }

    /**
     * @test
     */
    public function inserting_and_retrieving_messages(): void
    {
        $repository = $this->messageRepository();
        $eventUuid = $this->eventId();
        $message1 = $this->createMessage('one')->withHeader(
                Header::AGGREGATE_ROOT_VERSION,
                0
            );
        $message2 = $this->createMessage('two')->withHeaders(
            [
                Header::EVENT_ID => $eventUuid,
                Header::AGGREGATE_ROOT_VERSION => 1,
            ]
        );

        $repository->persist($message1, $message2);
        /** @var list<Message> $messages */
        $messages = iterator_to_array($repository->retrieveAll($this->aggregateRootId));

        self::assertCount(2, $messages);
        self::assertEquals($this->aggregateRootId, $messages[0]->aggregateRootId());
        self::assertIsString($messages[0]->header(Header::EVENT_ID));
        self::assertEquals($eventUuid, $messages[1]->header(Header::EVENT_ID));
    }

    /**
     * @test
     */
    public function retrieving_messages_after_a_version(): void
    {
        $repository = $this->messageRepository();
        $message1 = $this->createMessage('one')->withHeader(Header::AGGREGATE_ROOT_VERSION, 2);
        $message2 = $this->createMessage('two')->withHeader(Header::AGGREGATE_ROOT_VERSION, 3);

        $repository->persist($message1, $message2);
        /** @var list<Message> $messages */
        $messages = iterator_to_array($repository->retrieveAllAfterVersion($this->aggregateRootId, 2));
        $noMessages = iterator_to_array($repository->retrieveAllAfterVersion($this->aggregateRootId, 3));

        self::assertCount(1, $messages);
        self::assertCount(0, $noMessages);
    }

    /**
     * @test
     */
    public function inserting_no_messages(): void
    {
        $repository = $this->messageRepository();

        $repository->persist();
        $messages = iterator_to_array($repository->retrieveAll($this->aggregateRootId));

        self::assertCount(0, $messages);
    }

    /**
     * @test
     */
    public function failing_to_persist_messages(): void
    {
        $this->tableName = 'invalid';
        $repository = $this->messageRepository();

        self::expectException(UnableToPersistMessages::class);

        $message = $this->createMessage('one');
        $repository->persist($message);
    }

    /**
     * @test
     */
    public function failing_to_retrieve_all_messages(): void
    {
        $this->tableName = 'invalid';
        $repository = $this->messageRepository();

        self::expectException(UnableToRetrieveMessages::class);

        $repository->retrieveAll($this->aggregateRootId);
    }

    /**
     * @test
     */
    public function failing_to_retrieve_messages_after_version(): void
    {
        $this->tableName = 'invalid';
        $repository = $this->messageRepository();

        self::expectException(UnableToRetrieveMessages::class);

        $repository->retrieveAllAfterVersion($this->aggregateRootId, 5);
    }
}
