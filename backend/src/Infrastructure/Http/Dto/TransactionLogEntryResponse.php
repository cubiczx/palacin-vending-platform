<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Domain\Model\TransactionLogEntry;

final readonly class TransactionLogEntryResponse
{
    public function __construct(
        public ?string $id,
        public string $product,
        public float $price,
        public float $amountInserted,
        public CoinsResponse $changeReturned,
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
