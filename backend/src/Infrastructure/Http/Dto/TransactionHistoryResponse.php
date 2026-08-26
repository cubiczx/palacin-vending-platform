<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

final readonly class TransactionHistoryResponse
{
    /** @param list<TransactionLogEntryResponse> $items */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }

    /** @param array{items: list<\App\Domain\Model\TransactionLogEntry>, total: int} $result */
    public static function fromResult(array $result): self
    {
        return new self(
            items: array_map(TransactionLogEntryResponse::fromEntry(...), $result['items']),
            total: $result['total'],
        );
    }
}
