<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\FullProductView;
use OpenApi\Attributes as OA;

final readonly class FullProductResponse
{
    public function __construct(
        #[OA\Property(type: 'string', example: 'WATER')]
        public string $sku,
        #[OA\Property(type: 'string', example: 'Water')]
        public string $name,
        #[OA\Property(type: 'number', format: 'float', example: 0.65)]
        public float $price,
        #[OA\Property(type: 'integer', example: 10)]
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
