<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Query;

use App\Application\Query\GetFullMachineStateQuery;
use App\Application\Query\GetFullMachineStateQueryHandler;
use App\Domain\Model\Coin;
use App\Domain\Model\ProductSku;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;

final class GetFullMachineStateQueryHandlerTest extends TestCase
{
    public function testReturnsExactStockPerProduct(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog(sodaStock: 3, waterStock: 7));
        $handler = new GetFullMachineStateQueryHandler($repository);

        $view = $handler(new GetFullMachineStateQuery('machine-01'));

        $soda = current(array_filter($view->products, static fn ($p) => $p->sku === ProductSku::SODA));
        $water = current(array_filter($view->products, static fn ($p) => $p->sku === ProductSku::WATER));

        self::assertSame(3, $soda->stock);
        self::assertSame(7, $water->stock);
    }

    public function testReturnsChangeInventoryAsCentsToQuantityMap(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog());
        $handler = new GetFullMachineStateQueryHandler($repository);

        $view = $handler(new GetFullMachineStateQuery('machine-01'));

        self::assertSame(20, $view->changeInventory[Coin::ONE_EURO->value]);
        self::assertSame(20, $view->changeInventory[Coin::TWENTY_FIVE_CENTS->value]);
    }

    public function testThrowsWhenMachineDoesNotExist(): void
    {
        $handler = new GetFullMachineStateQueryHandler(new InMemoryVendingMachineRepository());

        $this->expectException(\RuntimeException::class);

        $handler(new GetFullMachineStateQuery('unknown-machine'));
    }
}
