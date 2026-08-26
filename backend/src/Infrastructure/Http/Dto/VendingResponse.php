<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Dto;

use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingResult;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

final readonly class VendingResponse
{
    public function __construct(
        #[OA\Property(type: 'string', enum: [ProductSku::WATER, ProductSku::JUICE, ProductSku::SODA], example: 'SODA')]
        public string $product,
        #[OA\Property(ref: new Model(type: CoinsResponse::class))]
        public CoinsResponse $change,
    ) {
    }

    public static function fromResult(VendingResult $result): self
    {
        return new self(
            product: $result->product->value,
            change: CoinsResponse::fromCents($result->changeReturned),
        );
    }
}
