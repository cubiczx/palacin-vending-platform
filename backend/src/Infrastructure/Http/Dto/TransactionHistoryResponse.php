<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final readonly class TransactionHistoryResponse
{
    /** @param list<TransactionLogEntryResponse> $items */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: TransactionLogEntryResponse::class)))]
        public array $items,
        #[OA\Property(type: 'integer', example: 42)]
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
