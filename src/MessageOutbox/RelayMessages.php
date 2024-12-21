<?php
declare(strict_types=1);

namespace EventSauce\MessageOutbox;

interface RelayMessages
{
    public function publishBatch(int $batchSize, ?int $commitSize = 1): int;
}
