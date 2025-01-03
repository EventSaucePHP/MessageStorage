<?php

namespace EventSauce\MessageOutbox\TestTooling;

use Doctrine\DBAL\Tools\DsnParser;

trait DoctrineConnectionTrait
{
    abstract function formatDsn(): string;

    /**
     * @return array<string, mixed>
     */
    protected function getConnectionParams(): array
    {
        $dsn = $this->formatDsn();
        if (class_exists(DsnParser::class)) {
            if (str_starts_with($this->formatDsn(), 'mysql')) {
                $parserParams = ['mysql' => 'pdo_mysql'];
            } else {
                $parserParams = ['pgsql' => 'pdo_pgsql'];
            }
            $parser = new DsnParser($parserParams);
            return $parser->parse($dsn);
        }
        return ['url' => $dsn];
    }
}
