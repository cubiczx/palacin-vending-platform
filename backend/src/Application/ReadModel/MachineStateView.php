<?php

declare(strict_types=1);

namespace App\Application\ReadModel;

use App\Domain\Model\Money;

final readonly class MachineStateView
{
    /** @param list<ProductView> $products */
    public function __construct(
        public array $products,
        public Money $insertedAmount,
    ) {
    }
}
