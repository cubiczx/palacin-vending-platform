<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Model\TransactionLogEntry;
use App\Domain\Model\TransactionLogFilter;
use App\Domain\Repository\TransactionLogRepositoryInterface;

final readonly class GetTransactionHistoryQueryHandler
{
    public function __construct(
        private TransactionLogRepositoryInterface $transactionLogs,
    ) {
    }

    /** @return array{items: list<TransactionLogEntry>, total: int} */
    public function __invoke(GetTransactionHistoryQuery $query): array
    {
        return $this->transactionLogs->search(new TransactionLogFilter(
            from: $query->from,
            to: $query->to,
            product: $query->product,
            page: $query->page,
            perPage: $query->perPage,
        ));
    }
}
