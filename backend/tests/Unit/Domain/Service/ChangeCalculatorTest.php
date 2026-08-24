<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Service;

use App\Domain\Exception\ExactChangeUnavailableException;
use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Service\ChangeCalculator;
use PHPUnit\Framework\TestCase;

final class ChangeCalculatorTest extends TestCase
{
    private ChangeCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new ChangeCalculator();
    }

    public function testZeroAmountReturnsNoCoins(): void
    {
        $result = $this->calculator->calculate(Money::zero(), ChangeInventory::fromCounts([25 => 4]));

        self::assertSame([], $result);
    }

    public function testGreedyPicksLargestDenominationsFirst(): void
    {
        // 0.35 due -> greedy: one 0.25 + one 0.10
        $available = ChangeInventory::fromCounts([5 => 10, 10 => 10, 25 => 10, 100 => 10]);

        $result = $this->calculator->calculate(Money::fromCents(35), $available);

        self::assertSame([25 => 1, 10 => 1], $result);
    }

    public function testFallsBackToSmallerDenominationsWhenLargerRunOut(): void
    {
        // 0.30 due, no 0.25 available -> must use three 0.10
        $available = ChangeInventory::fromCounts([5 => 10, 10 => 10, 25 => 0, 100 => 10]);

        $result = $this->calculator->calculate(Money::fromCents(30), $available);

        self::assertSame([10 => 3], $result);
    }

    public function testThrowsWhenExactChangeCannotBeMade(): void
    {
        // Documented greedy limitation: plenty of small coins but the
        // greedy path still can't complete this particular amount.
        $available = ChangeInventory::fromCounts([5 => 0, 10 => 0, 25 => 0, 100 => 0]);

        $this->expectException(ExactChangeUnavailableException::class);

        $this->calculator->calculate(Money::fromCents(30), $available);
    }

    public function testDoesNotMutateTheSuppliedInventory(): void
    {
        $available = ChangeInventory::fromCounts([25 => 5]);

        $this->calculator->calculate(Money::fromCents(25), $available);

        self::assertSame(5, $available->quantityOf(\App\Domain\Model\Coin::TWENTY_FIVE_CENTS));
    }
}
