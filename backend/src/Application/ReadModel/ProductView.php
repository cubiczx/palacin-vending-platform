<?php

declare(strict_types=1);

namespace App\Application\ReadModel;

use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;

/** Customer-facing product view: no exact stock count exposed. */
final readonly class ProductView
{
    public function __construct(
        public ProductSku $sku,
        public string $name,
        public Money $price,
        public bool $inStock,
    ) {
    }
}
