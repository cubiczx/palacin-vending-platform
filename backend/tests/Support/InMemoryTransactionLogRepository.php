<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Model\TransactionLogEntry;
use App\Domain\Model\TransactionLogFilter;
use App\Domain\Repository\TransactionLogRepositoryInterface;

final class InMemoryTransactionLogRepository implements TransactionLogRepositoryInterface
{
    /** @var list<TransactionLogEntry> */
    private array $entries = [];

    public function record(TransactionLogEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /** @return array{items: list<TransactionLogEntry>, total: int} */
    public function search(TransactionLogFilter $filter): array
    {
        return ['items' => $this->entries, 'total' => count($this->entries)];
    }

    /** @return list<TransactionLogEntry> */
    public function all(): array
    {
        return $this->entries;
    }
}
