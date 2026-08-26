<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\FullMachineStateView;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final readonly class FullMachineStateResponse
{
    /** @param list<FullProductResponse> $products */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: FullProductResponse::class)))]
        public array $products,
        #[OA\Property(ref: new Model(type: CoinsResponse::class))]
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
