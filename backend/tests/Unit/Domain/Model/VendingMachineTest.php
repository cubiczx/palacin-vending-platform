<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Exception\ExactChangeUnavailableException;
use App\Domain\Exception\InsufficientFundsException;
use App\Domain\Exception\OutOfStockException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Model\Coin;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Service\ChangeCalculator;
use App\Tests\Support\VendingMachineFixture;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class VendingMachineTest extends TestCase
{
    private ChangeCalculator $changeCalculator;
    private DateTimeImmutable $now;

    protected function setUp(): void
    {
        $this->changeCalculator = new ChangeCalculator();
        $this->now = new DateTimeImmutable('2026-08-24T10:00:00+00:00');
    }

    public function testInsertCoinIncreasesInsertedAmount(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();

        $machine->insertCoin(Coin::ONE_EURO);
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);

        self::assertSame(125, $machine->insertedAmount()->cents());
    }

    public function testExample1BuySodaWithExactChange(): void
    {
        // 1, 0.25, 0.25, GET-SODA -> SODA (no change due)
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::ONE_EURO);
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);

        $result = $machine->selectProduct(ProductSku::SODA, $this->changeCalculator, $this->now);

        self::assertSame(ProductSku::SODA, $result->product);
        self::assertSame([], $result->changeReturned);
        self::assertTrue($machine->insertedAmount()->isZero(), 'session must reset after a purchase');
    }

    public function testExample2ReturnCoinGivesBackWhatWasInserted(): void
    {
        // 0.10, 0.10, RETURN-COIN -> 0.10, 0.10
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::TEN_CENTS);
        $machine->insertCoin(Coin::TEN_CENTS);

        $returned = $machine->returnCoins();

        self::assertSame([10 => 2], $returned);
        self::assertTrue($machine->insertedAmount()->isZero());
    }

    public function testExample3BuyWaterWithoutExactChange(): void
    {
        // 1, GET-WATER -> WATER, 0.25, 0.10 (water costs 0.65)
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::ONE_EURO);

        $result = $machine->selectProduct(ProductSku::WATER, $this->changeCalculator, $this->now);

        self::assertSame(ProductSku::WATER, $result->product);
        self::assertSame([25 => 1, 10 => 1], $result->changeReturned);
    }

    public function testSelectProductDecrementsStock(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::ONE_EURO);
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);

        $machine->selectProduct(ProductSku::SODA, $this->changeCalculator, $this->now);

        $soda = current(array_filter($machine->products(), static fn ($p) => $p->sku() === ProductSku::SODA));
        self::assertNotFalse($soda);
        self::assertSame(4, $soda->stock());
    }

    public function testSelectProductWithOutOfStockProductThrowsAndDoesNotChargeCustomer(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog(sodaStock: 0);
        $machine->insertCoin(Coin::ONE_EURO);
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);

        $this->expectException(OutOfStockException::class);

        try {
            $machine->selectProduct(ProductSku::SODA, $this->changeCalculator, $this->now);
        } finally {
            self::assertSame(150, $machine->insertedAmount()->cents(), 'money must remain inserted after a failed purchase');
        }
    }

    public function testSelectProductWithInsufficientFundsThrows(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::TWENTY_FIVE_CENTS);

        $this->expectException(InsufficientFundsException::class);

        $machine->selectProduct(ProductSku::SODA, $this->changeCalculator, $this->now);
    }

    public function testSelectUnknownProductThrows(): void
    {
        $machine = \App\Domain\Model\VendingMachine::create(
            id: 'machine-02',
            products: [
                new \App\Domain\Model\Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5),
            ],
            changeInventory: VendingMachineFixture::plentifulChange(),
        );

        $this->expectException(ProductNotFoundException::class);

        $machine->selectProduct(ProductSku::WATER, $this->changeCalculator, $this->now);
    }

    public function testSelectProductWithoutAvailableChangeThrowsAndDoesNotVendOrChargeCustomer(): void
    {
        $machine = \App\Domain\Model\VendingMachine::create(
            id: 'machine-01',
            products: [new \App\Domain\Model\Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: VendingMachineFixture::emptyChange(),
        );
        $machine->insertCoin(Coin::ONE_EURO);

        $this->expectException(ExactChangeUnavailableException::class);

        try {
            $machine->selectProduct(ProductSku::WATER, $this->changeCalculator, $this->now);
        } finally {
            self::assertSame(100, $machine->insertedAmount()->cents(), 'customer must not be charged if change cannot be given');

            $water = $machine->products()[0];
            self::assertSame(5, $water->stock(), 'stock must not decrement if the sale could not complete');
        }
    }

    public function testInsertedCoinsAreDepositedIntoChangeInventoryAfterAPurchase(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::ONE_EURO);

        $machine->selectProduct(ProductSku::WATER, $this->changeCalculator, $this->now);

        // The inserted 1 EUR coin should now be part of the machine's stock,
        // even though it wasn't used to pay this transaction's own change.
        self::assertSame(21, $machine->changeInventory()->quantityOf(Coin::ONE_EURO));
    }

    public function testSelectProductRecordsAProductVendedEvent(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::ONE_EURO);

        $machine->selectProduct(ProductSku::WATER, $this->changeCalculator, $this->now);

        $events = $machine->releaseEvents();
        self::assertCount(1, $events);
        self::assertSame(ProductSku::WATER, $events[0]->product);
        self::assertSame($this->now, $events[0]->occurredAt);
    }

    public function testReleaseEventsClearsThePendingList(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();
        $machine->insertCoin(Coin::ONE_EURO);
        $machine->selectProduct(ProductSku::WATER, $this->changeCalculator, $this->now);

        $machine->releaseEvents();

        self::assertSame([], $machine->releaseEvents());
    }

    public function testRestockProductIncreasesStock(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog(sodaStock: 2);

        $machine->restockProduct(ProductSku::SODA, 8);

        $soda = current(array_filter($machine->products(), static fn ($p) => $p->sku() === ProductSku::SODA));
        self::assertInstanceOf(Product::class, $soda);
        self::assertSame(10, $soda->stock());
    }

    public function testUpdateProductPriceChangesPrice(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();

        $machine->updateProductPrice(ProductSku::SODA, Money::fromCents(175));

        $soda = current(array_filter($machine->products(), static fn ($p) => $p->sku() === ProductSku::SODA));
        self::assertInstanceOf(Product::class, $soda);
        self::assertSame(175, $soda->price()->cents());
    }

    public function testSetChangeInventoryReplacesInventory(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog();

        $machine->setChangeInventory(\App\Domain\Model\ChangeInventory::fromCounts([100 => 1]));

        self::assertSame(1, $machine->changeInventory()->quantityOf(Coin::ONE_EURO));
        self::assertSame(0, $machine->changeInventory()->quantityOf(Coin::TWENTY_FIVE_CENTS));
    }
}
