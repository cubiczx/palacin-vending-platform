<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\FullMachineStateView;

final readonly class FullMachineStateResponse
{
    /** @param list<FullProductResponse> $products */
    public function __construct(
        public array $products,
        public CoinsResponse $changeInventory,
    ) {
    }

    public static function fromView(FullMachineStateView $view): self
    {
        return new self(
            products: array_map(FullProductResponse::fromView(...), $view->products),
            changeInventory: CoinsResponse::fromCents($view->changeInventory),
        );
    }
}
