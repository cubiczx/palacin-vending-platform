<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Coin;
use PHPUnit\Framework\TestCase;

final class ChangeInventoryTest extends TestCase
{
    public function testFromCountsDefaultsMissingDenominationsToZero(): void
    {
        $inventory = ChangeInventory::fromCounts([25 => 3]);

        self::assertSame(0, $inventory->quantityOf(Coin::FIVE_CENTS));
        self::assertSame(3, $inventory->quantityOf(Coin::TWENTY_FIVE_CENTS));
    }

    public function testWithdrawReducesQuantityAndIsImmutable(): void
    {
        $original = ChangeInventory::fromCounts([25 => 5]);

        $after = $original->withdraw(Coin::TWENTY_FIVE_CENTS, 2);

        self::assertSame(5, $original->quantityOf(Coin::TWENTY_FIVE_CENTS), 'original must stay unchanged');
        self::assertSame(3, $after->quantityOf(Coin::TWENTY_FIVE_CENTS));
    }

    public function testWithdrawingMoreThanAvailableThrows(): void
    {
        $inventory = ChangeInventory::fromCounts([25 => 1]);

        $this->expectException(\InvalidArgumentException::class);

        $inventory->withdraw(Coin::TWENTY_FIVE_CENTS, 2);
    }

    public function testDepositIncreasesQuantity(): void
    {
        $inventory = ChangeInventory::fromCounts([100 => 2]);

        $after = $inventory->deposit(Coin::ONE_EURO, 3);

        self::assertSame(5, $after->quantityOf(Coin::ONE_EURO));
    }

    public function testNegativeInitialQuantityThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ChangeInventory::fromCounts([25 => -1]);
    }
}
