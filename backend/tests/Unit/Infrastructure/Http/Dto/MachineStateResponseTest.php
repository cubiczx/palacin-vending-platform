<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Dto;

use App\Application\ReadModel\MachineStateView;
use App\Application\ReadModel\ProductView;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\MachineStateResponse;
use PHPUnit\Framework\TestCase;

final class MachineStateResponseTest extends TestCase
{
    public function testFromViewMapsProductsAndInsertedAmount(): void
    {
        $view = new MachineStateView(
            products: [
                new ProductView(ProductSku::WATER, 'Water', Money::fromCents(65), true),
                new ProductView(ProductSku::SODA, 'Soda', Money::fromCents(150), false),
            ],
            insertedAmount: Money::fromCents(25),
        );

        $response = MachineStateResponse::fromView($view);

        self::assertCount(2, $response->products);
        self::assertSame('WATER', $response->products[0]->sku);
        self::assertSame('SODA', $response->products[1]->sku);
        self::assertSame(0.25, $response->insertedAmount);
    }

    public function testFromViewWithEmptyCatalog(): void
    {
        $view = new MachineStateView(products: [], insertedAmount: Money::zero());

        $response = MachineStateResponse::fromView($view);

        self::assertSame([], $response->products);
        self::assertSame(0.0, $response->insertedAmount);
    }
}
