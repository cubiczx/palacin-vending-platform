<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Dto;

use App\Application\ReadModel\FullMachineStateView;
use App\Application\ReadModel\FullProductView;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\FullMachineStateResponse;
use PHPUnit\Framework\TestCase;

final class FullMachineStateResponseTest extends TestCase
{
    public function testFromViewMapsProductsAndChangeInventory(): void
    {
        $view = new FullMachineStateView(
            products: [
                new FullProductView(ProductSku::WATER, 'Water', Money::fromCents(65), 5),
            ],
            changeInventory: [5 => 20, 10 => 20, 25 => 20, 100 => 20],
        );

        $response = FullMachineStateResponse::fromView($view);

        self::assertCount(1, $response->products);
        self::assertSame(5, $response->products[0]->stock);
        self::assertSame(
            ['0.05' => 20, '0.10' => 20, '0.25' => 20, '1.00' => 20],
            $response->changeInventory->coins,
        );
    }
}
