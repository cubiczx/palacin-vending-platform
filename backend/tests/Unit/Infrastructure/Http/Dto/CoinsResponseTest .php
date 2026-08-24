<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Http\Dto;

use App\Infrastructure\Http\Dto\CoinsResponse;
use PHPUnit\Framework\TestCase;

final class CoinsResponseTest extends TestCase
{
    public function testFormatsCentsAsEuroStringKeys(): void
    {
        $response = CoinsResponse::fromCents([25 => 1, 10 => 2]);

        self::assertSame(['0.25' => 1, '0.10' => 2], $response->coins);
    }

    public function testFormatsOneEuroWithTwoDecimals(): void
    {
        $response = CoinsResponse::fromCents([100 => 3]);

        self::assertSame(['1.00' => 3], $response->coins);
    }

    public function testEmptyChangeProducesEmptyMap(): void
    {
        $response = CoinsResponse::fromCents([]);

        self::assertSame([], $response->coins);
    }
}
