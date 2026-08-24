<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testFromEurosConvertsToCentsAvoidingFloatRounding(): void
    {
        // 0.1 + 0.2 famously != 0.3 in float arithmetic; the cents
        // conversion must not leak that imprecision.
        $money = Money::fromEuros(1.10);

        self::assertSame(110, $money->cents());
    }

    public function testAddSumsCents(): void
    {
        $result = Money::fromCents(65)->add(Money::fromCents(35));

        self::assertSame(100, $result->cents());
    }

    public function testSubtractReturnsDifference(): void
    {
        $result = Money::fromCents(100)->subtract(Money::fromCents(65));

        self::assertSame(35, $result->cents());
    }

    public function testSubtractBelowZeroThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromCents(50)->subtract(Money::fromCents(100));
    }

    public function testNegativeAmountCannotBeConstructed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Money::fromCents(-1);
    }

    public function testIsGreaterThanOrEqualTo(): void
    {
        self::assertTrue(Money::fromCents(100)->isGreaterThanOrEqualTo(Money::fromCents(65)));
        self::assertTrue(Money::fromCents(65)->isGreaterThanOrEqualTo(Money::fromCents(65)));
        self::assertFalse(Money::fromCents(50)->isGreaterThanOrEqualTo(Money::fromCents(65)));
    }

    public function testZeroIsZero(): void
    {
        self::assertTrue(Money::zero()->isZero());
        self::assertFalse(Money::fromCents(1)->isZero());
    }

    public function testEqualsComparesByValue(): void
    {
        self::assertTrue(Money::fromCents(100)->equals(Money::fromCents(100)));
        self::assertFalse(Money::fromCents(100)->equals(Money::fromCents(99)));
    }

    public function testToEurosConvertsBack(): void
    {
        self::assertSame(1.5, Money::fromCents(150)->toEuros());
    }
}
