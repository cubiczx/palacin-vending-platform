<?php

declare(strict_types=1);

namespace App\Domain\Model;

use DateTimeImmutable;

final readonly class TransactionLogEntry
{
    /** @param array<int, int> $changeReturned Coin value in cents => quantity */
    public function __construct(
        public ?string $id,
        public ProductSku $product,
        public Money $price,
        public Money $amountInserted,
        public array $changeReturned,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
