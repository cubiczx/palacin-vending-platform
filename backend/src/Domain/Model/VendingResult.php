<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Outcome of a successful purchase: the product vended and the exact
 * coins returned as change.
 */
final readonly class VendingResult
{
    /** @param array<int, int> $changeReturned Coin value in cents => quantity */
    public function __construct(
        public ProductSku $product,
        public array $changeReturned,
    ) {
    }
}
