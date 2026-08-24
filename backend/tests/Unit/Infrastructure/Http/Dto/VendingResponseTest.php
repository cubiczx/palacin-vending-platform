<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Dto;

use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingResult;
use App\Infrastructure\Http\Dto\VendingResponse;
use PHPUnit\Framework\TestCase;

final class VendingResponseTest extends TestCase
{
    public function testFromResultMapsProductAndChange(): void
    {
        $result = new VendingResult(ProductSku::WATER, [25 => 1, 10 => 1]);

        $response = VendingResponse::fromResult($result);

        self::assertSame('WATER', $response->product);
        self::assertSame(['0.25' => 1, '0.10' => 1], $response->change->coins);
    }

    public function testFromResultWithExactChangeHasEmptyCoins(): void
    {
        $result = new VendingResult(ProductSku::SODA, []);

        $response = VendingResponse::fromResult($result);

        self::assertSame([], $response->change->coins);
    }
}
