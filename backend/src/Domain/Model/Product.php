<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\OutOfStockException;

/**
 * A product tracked by the machine. Mutable by design: stock and price
 * change over its lifetime as part of the VendingMachine aggregate's
 * behaviour. Only ever mutated through the aggregate root.
 */
final class Product
{
    public function __construct(
        private readonly ProductSku $sku,
        private readonly string $name,
        private Money $price,
        private int $stock,
        //private string $slot, // TODO create Model Slot?
    ) {
        if ($stock < 0) {
            throw new \InvalidArgumentException('Stock cannot be negative.');
        }
    }

    public function sku(): ProductSku
    {
        return $this->sku;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function price(): Money
    {
        return $this->price;
    }

    public function stock(): int
    {
        return $this->stock;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function decrementStock(): void
    {
        if ($this->stock <= 0) {
            throw OutOfStockException::forProduct($this->sku);
        }

        $this->stock--;
    }

    public function restock(int $quantity): void
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Restock quantity cannot be negative.');
        }

        $this->stock += $quantity;
    }

    public function changePrice(Money $newPrice): void
    {
        $this->price = $newPrice;
    }
}
