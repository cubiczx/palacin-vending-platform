<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Exception\OutOfStockException;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    private function makeProduct(int $stock = 1): Product
    {
        return new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), $stock);
    }

    public function testDecrementStockReducesByOne(): void
    {
        $product = $this->makeProduct(stock: 2);

        $product->decrementStock();

        self::assertSame(1, $product->stock());
    }

    public function testDecrementStockAtZeroThrows(): void
    {
        $product = $this->makeProduct(stock: 0);

        $this->expectException(OutOfStockException::class);

        $product->decrementStock();
    }

    public function testIsInStockReflectsCurrentStock(): void
    {
        self::assertTrue($this->makeProduct(stock: 1)->isInStock());
        self::assertFalse($this->makeProduct(stock: 0)->isInStock());
    }

    public function testRestockIncreasesStock(): void
    {
        $product = $this->makeProduct(stock: 3);

        $product->restock(7);

        self::assertSame(10, $product->stock());
    }

    public function testRestockWithNegativeQuantityThrows(): void
    {
        $product = $this->makeProduct();

        $this->expectException(\InvalidArgumentException::class);

        $product->restock(-1);
    }

    public function testChangePriceUpdatesPrice(): void
    {
        $product = $this->makeProduct();

        $product->changePrice(Money::fromCents(200));

        self::assertSame(200, $product->price()->cents());
    }
}
