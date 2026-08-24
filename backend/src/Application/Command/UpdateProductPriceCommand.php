<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;

final readonly class UpdateProductPriceCommand
{
    public function __construct(
        public string $machineId,
        public ProductSku $sku,
        public Money $newPrice,
    ) {
    }
}
