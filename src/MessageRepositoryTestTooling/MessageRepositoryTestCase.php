<?php

namespace EventSauce\MessageRepository\TestTooling;

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\DefaultHeadersDecorator;
use EventSauce\EventSourcing\DotSeparatedSnakeCaseInflector;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\OffsetCursor;
use EventSauce\EventSourcing\PaginationCursor;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\EventSourcing\UnableToRetrieveMessages;
use EventSauce\MessageOutbox\TestTooling\DummyEvent;
use PHPUnit\Framework\TestCase;

use Ramsey\Uuid\Uuid;

use function array_slice;
use function get_class;
use function iterator_to_array;

abstract class MessageRepositoryTestCase extends TestCase
{
    protected string $tableName = 'domain_messages_uuid';
    protected AggregateRootId $aggregateRootId;

    abstract protected function messageRepository(): MessageRepository;
    abstract protected function aggregateRootId(): AggregateRootId;
    abstract protected function eventId(): string;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aggregateRootId = $this->aggregateRootId();
    }

    protected function createMessage(string $value, AggregateRootId $id = null): Message
    {
        $id ??= $this->aggregateRootId;
        $type = (new DotSeparatedSnakeCaseInflector())->classNameToType(get_class($id));

        return (new DefaultHeadersDecorator())
            ->decorate(new Message(new DummyEvent($value)))
            ->withHeader(Header::AGGREGATE_ROOT_ID, $id)
            ->withHeader(Header::AGGREGATE_ROOT_ID_TYPE, $type);
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
    public function fetching_the_first_page_for_pagination(): void
    {
        $repository = $this->messageRepository();
        $messages = [];

        for ($i = 0; $i < 10; $i++) {
            $messages[] = $this->createMessage('numnber: ' . $i)->withHeader(Header::AGGREGATE_ROOT_VERSION, $i)
                ->withHeader(Header::EVENT_ID, Uuid::uuid4()->toString());
        }

        $repository->persist(...$messages);

        $page = $repository->paginate(4, OffsetCursor::fromStart());
        $messagesFromPage = iterator_to_array($page, false);
        $expectedMessages = array_slice($messages, 0, 4);
        $cursor = $page->getReturn();

        self::assertEquals($expectedMessages, $messagesFromPage);
        self::assertInstanceOf(PaginationCursor::class, $cursor);
    }

    /**
     * @test
     */
    public function fetching_the_next_page_for_pagination(): void
    {
        $repository = $this->messageRepository();
        $messages = [];

        for ($i = 0; $i < 10; $i++) {
            $messages[] = $this->createMessage('number: ' . $i, $this->aggregateRootId())
                ->withHeader(Header::AGGREGATE_ROOT_VERSION, 11 - $i)
                ->withHeader(Header::EVENT_ID, Uuid::uuid4()->toString());
        }

        $repository->persist(...$messages);
        $page = $repository->paginate(4, OffsetCursor::fromStart());
        iterator_to_array($page, false);
        $cursor = $page->getReturn();

        $page = $repository->paginate(4, $cursor);
        $messagesFromPage = iterator_to_array($page, false);
        $expectedMessages = array_slice($messages, 4, 4);

        self::assertEquals($expectedMessages, $messagesFromPage);
        self::assertInstanceOf(PaginationCursor::class, $cursor);
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

    /**
     * @test
     */
    public function failing_to_paginate(): void
    {
        $this->tableName = 'invalid';
        $repository = $this->messageRepository();

        self::expectException(UnableToRetrieveMessages::class);

        iterator_to_array($repository->paginate(10, OffsetCursor::fromStart()));
    }
}
