<?php

declare(strict_types=1);

namespace App\Application\ReadModel;

use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;

/** Service-facing product view: exact stock exposed for restocking decisions. */
final readonly class FullProductView
{
    public function __construct(
        public ProductSku $sku,
        public string $name,
        public Money $price,
        public int $stock,
    ) {
    }
}
