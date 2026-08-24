<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use DateTimeImmutable;

/**
 * Raised when a product has been successfully vended. Consumed by an
 * Application-layer listener to persist an entry in the transaction
 * log — the domain itself has no knowledge of persistence or logging.
 */
final readonly class ProductVendedEvent
{
    /** @param array<int, int> $changeReturned Coin value in cents => quantity */
    public function __construct(
        public ProductSku $product,
        public Money $price,
        public Money $amountInserted,
        public array $changeReturned,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
