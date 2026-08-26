<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\ProductView;
use App\Domain\Model\ProductSku;
use OpenApi\Attributes as OA;

final readonly class ProductResponse
{
    public function __construct(
        #[OA\Property(type: 'string', enum: [ProductSku::WATER, ProductSku::JUICE, ProductSku::SODA], example: 'SODA')]
        public string $sku,
        #[OA\Property(type: 'string', example: 'Soda')]
        public string $name,
        #[OA\Property(type: 'number', format: 'float', example: 1.5)]
        public float $price,
        #[OA\Property(type: 'boolean', example: true)]
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
