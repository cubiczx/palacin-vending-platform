<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\ProductSku;

final readonly class SelectProductCommand
{
    public function __construct(
        public string $machineId,
        public ProductSku $sku,
    ) {
    }
}
