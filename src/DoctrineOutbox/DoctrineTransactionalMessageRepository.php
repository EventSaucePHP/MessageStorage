<?php

namespace EventSauce\MessageOutbox\DoctrineOutbox;

use Doctrine\DBAL\Connection;
use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Header;
use EventSauce\EventSourcing\Message;
use EventSauce\EventSourcing\MessageRepository;
use EventSauce\EventSourcing\PaginationCursor;
use EventSauce\EventSourcing\UnableToPersistMessages;
use EventSauce\MessageOutbox\OutboxRepository;
use Generator;
use Ramsey\Uuid\Uuid;
use Throwable;

class DoctrineTransactionalMessageRepository implements MessageRepository
{
    public function __construct(
        private Connection $connection,
        private MessageRepository $messageRepository,
        private OutboxRepository $outboxRepository
    ) {}

    public function persist(Message ...$messages): void
    {
        try {
            $messages = array_map(static function (Message $message) {
                return $message->header(Header::EVENT_ID) === null
                    ? $message->withHeader(Header::EVENT_ID, Uuid::uuid4()->toString())
                    : $message;
            }, $messages);

            $this->connection->beginTransaction();

            try {
                $this->messageRepository->persist(...$messages);
                $this->outboxRepository->persist(...$messages);
                $this->connection->commit();
            } catch (Throwable $exception) {
                $this->connection->rollBack();
                throw $exception;
            }
        } catch (Throwable $exception) {
            throw UnableToPersistMessages::dueTo('', $exception);
        }
    }

    public function retrieveAll(AggregateRootId $id): Generator
    {
        return $this->messageRepository->retrieveAll($id);
    }

    public function retrieveAllAfterVersion(AggregateRootId $id, int $aggregateRootVersion): Generator
    {
        return $this->messageRepository->retrieveAllAfterVersion($id, $aggregateRootVersion);
    }

    public function paginate(PaginationCursor $cursor): Generator
    {
        return $this->messageRepository->paginate($cursor);
    }
}
