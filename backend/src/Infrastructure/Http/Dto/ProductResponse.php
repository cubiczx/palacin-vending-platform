<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\ProductView;

final readonly class ProductResponse
{
    public function __construct(
        public string $sku,
        public string $name,
        public float $price,
        public bool $inStock,
    ) {
    }

    public static function fromView(ProductView $view): self
    {
        return new self(
            sku: $view->sku->value,
            name: $view->name,
            price: $view->price->toEuros(),
            inStock: $view->inStock,
        );
    }
}
