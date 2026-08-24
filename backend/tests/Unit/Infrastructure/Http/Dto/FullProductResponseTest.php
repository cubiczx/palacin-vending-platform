<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Dto;

use App\Application\ReadModel\FullProductView;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Infrastructure\Http\Dto\FullProductResponse;
use PHPUnit\Framework\TestCase;

final class FullProductResponseTest extends TestCase
{
    public function testFromViewMapsAllFieldsIncludingExactStock(): void
    {
        $view = new FullProductView(ProductSku::JUICE, 'Juice', Money::fromCents(100), 7);

        $response = FullProductResponse::fromView($view);

        self::assertSame('JUICE', $response->sku);
        self::assertSame('Juice', $response->name);
        self::assertSame(1.0, $response->price);
        self::assertSame(7, $response->stock);
    }
}
