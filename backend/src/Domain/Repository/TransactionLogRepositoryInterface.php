<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\TransactionLogEntry;
use App\Domain\Model\TransactionLogFilter;

interface TransactionLogRepositoryInterface
{
    public function record(TransactionLogEntry $entry): void;

    /** @return array{items: list<TransactionLogEntry>, total: int} */
    public function search(TransactionLogFilter $filter): array;
}
