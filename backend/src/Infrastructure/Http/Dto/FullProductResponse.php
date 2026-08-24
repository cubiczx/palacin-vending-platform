<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\FullProductView;

final readonly class FullProductResponse
{
    public function __construct(
        public string $sku,
        public string $name,
        public float $price,
        public int $stock,
    ) {
    }

    public static function fromView(FullProductView $view): self
    {
        return new self(
            sku: $view->sku->value,
            name: $view->name,
            price: $view->price->toEuros(),
            stock: $view->stock,
        );
    }
}
