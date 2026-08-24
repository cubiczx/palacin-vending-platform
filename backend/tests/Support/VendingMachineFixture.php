<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingMachine;

final class VendingMachineFixture
{
    public static function withDefaultCatalog(
        string $id = 'machine-01',
        int $waterStock = 5,
        int $juiceStock = 5,
        int $sodaStock = 5,
    ): VendingMachine {
        return VendingMachine::create(
            id: $id,
            products: [
                new Product(ProductSku::WATER, 'Water', Money::fromCents(65), $waterStock),
                new Product(ProductSku::JUICE, 'Juice', Money::fromCents(100), $juiceStock),
                new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), $sodaStock),
            ],
            changeInventory: self::plentifulChange(),
        );
    }

    public static function plentifulChange(): ChangeInventory
    {
        return ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]);
    }

    public static function emptyChange(): ChangeInventory
    {
        return ChangeInventory::fromCounts([]);
    }
}
