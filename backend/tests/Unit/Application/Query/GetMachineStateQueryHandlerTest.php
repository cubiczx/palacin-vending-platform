<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Query;

use App\Application\Query\GetMachineStateQuery;
use App\Application\Query\GetMachineStateQueryHandler;
use App\Domain\Model\Coin;
use App\Domain\Model\ProductSku;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;

final class GetMachineStateQueryHandlerTest extends TestCase
{
    public function testReturnsProductsAndInsertedAmount(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);

        $repository = new InMemoryVendingMachineRepository();
        $repository->seed($machine);
        $handler = new GetMachineStateQueryHandler($repository);

        $view = $handler(new GetMachineStateQuery('machine-01'));

        self::assertCount(3, $view->products);
        self::assertSame(25, $view->insertedAmount->cents());
    }

    public function testProductViewDoesNotExposeExactStockOnlyInStockFlag(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog(sodaStock: 3));
        $handler = new GetMachineStateQueryHandler($repository);

        $view = $handler(new GetMachineStateQuery('machine-01'));

        $soda = current(array_filter($view->products, static fn ($p) => $p->sku === ProductSku::SODA));
        self::assertNotFalse($soda);

        self::assertTrue($soda->inStock);
        self::assertFalse(property_exists($soda, 'stock'), 'customer-facing view must never expose exact stock counts');
    }

    public function testOutOfStockProductIsReportedAsNotInStock(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog(sodaStock: 0));
        $handler = new GetMachineStateQueryHandler($repository);

        $view = $handler(new GetMachineStateQuery('machine-01'));

        $soda = current(array_filter($view->products, static fn ($p) => $p->sku === ProductSku::SODA));
        self::assertNotFalse($soda);
        self::assertFalse($soda->inStock);
    }

    public function testThrowsWhenMachineDoesNotExist(): void
    {
        $handler = new GetMachineStateQueryHandler(new InMemoryVendingMachineRepository());

        $this->expectException(\RuntimeException::class);

        $handler(new GetMachineStateQuery('unknown-machine'));
    }
}
