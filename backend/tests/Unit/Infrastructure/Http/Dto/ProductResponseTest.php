<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Dto;

use App\Application\ReadModel\ProductView;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\ProductResponse;
use PHPUnit\Framework\TestCase;

final class ProductResponseTest extends TestCase
{
    public function testFromViewMapsAllFields(): void
    {
        $view = new ProductView(ProductSku::WATER, 'Water', Money::fromCents(65), true);

        $response = ProductResponse::fromView($view);

        self::assertSame('WATER', $response->sku);
        self::assertSame('Water', $response->name);
        self::assertSame(0.65, $response->price);
        self::assertTrue($response->inStock);
    }

    public function testFromViewReflectsOutOfStock(): void
    {
        $view = new ProductView(ProductSku::SODA, 'Soda', Money::fromCents(150), false);

        $response = ProductResponse::fromView($view);

        self::assertFalse($response->inStock);
    }
}
