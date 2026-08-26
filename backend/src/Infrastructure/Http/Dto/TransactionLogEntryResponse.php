<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Domain\Model\TransactionLogEntry;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final readonly class TransactionLogEntryResponse
{
    public function __construct(
        #[OA\Property(type: 'string', nullable: true, example: '018f4a21-9d2e-7b1c-8e4f-5a6b7c8d9e0f')]
        public ?string $id,
        #[OA\Property(type: 'string', example: 'WATER')]
        public string $product,
        #[OA\Property(type: 'number', format: 'float', example: 0.65)]
        public float $price,
        #[OA\Property(type: 'number', format: 'float', example: 1.00)]
        public float $amountInserted,
        #[OA\Property(ref: new Model(type: CoinsResponse::class))]
        public CoinsResponse $changeReturned,
        #[OA\Property(type: 'string', format: 'date-time', example: '2026-03-31T12:00:00Z')]
        public string $occurredAt,
    ) {
    }

    public static function fromEntry(TransactionLogEntry $entry): self
    {
        return new self(
            id: $entry->id,
            product: $entry->product->value,
            price: $entry->price->toEuros(),
            amountInserted: $entry->amountInserted->toEuros(),
            changeReturned: CoinsResponse::fromCents($entry->changeReturned),
            occurredAt: $entry->occurredAt->format(DATE_ATOM),
        );
    }
}
