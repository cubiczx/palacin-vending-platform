<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\MachineStateView;

final readonly class MachineStateResponse
{
    /** @param list<ProductResponse> $products */
    public function __construct(
        public array $products,
        public float $insertedAmount,
    ) {
    }

    public static function fromView(MachineStateView $view): self
    {
        return new self(
            products: array_map(ProductResponse::fromView(...), $view->products),
            insertedAmount: $view->insertedAmount->toEuros(),
        );
    }
}
