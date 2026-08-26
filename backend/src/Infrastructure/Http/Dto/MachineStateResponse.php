<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Application\ReadModel\MachineStateView;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final readonly class MachineStateResponse
{
    /** @param list<ProductResponse> $products */
    public function __construct(
        #[OA\Property(type: 'array', items: new OA\Items(ref: new Model(type: ProductResponse::class)))]
        public array $products,
        #[OA\Property(type: 'number', format: 'float', example: 0.0)]
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
