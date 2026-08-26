<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\InvalidChangeQuantityException;

/**
 * Immutable snapshot of how many coins of each denomination the machine
 * currently holds for giving change. Every mutation returns a new instance.
 */
final readonly class ChangeInventory
{
    /** @param array<int, int> $counts Coin value in cents => quantity available */
    private function __construct(private array $counts)
    {
    }

    /** @param array<int, int> $counts Coin value in cents => quantity available */
    public static function fromCounts(array $counts): self
    {
        $normalized = [];
        foreach (Coin::cases() as $coin) {
            $quantity = $counts[$coin->value] ?? 0;
            if ($quantity < 0) {
                throw InvalidChangeQuantityException::forNegativeQuantity($coin->name);
            }
            $normalized[$coin->value] = $quantity;
        }

        return new self($normalized);
    }

    public static function empty(): self
    {
        return self::fromCounts([]);
    }

    public function quantityOf(Coin $coin): int
    {
        return $this->counts[$coin->value] ?? 0;
    }


    public function withdraw(Coin $coin, int $quantity): self
    {
        $available = $this->quantityOf($coin);
        if ($quantity > $available) {
            throw new \InvalidArgumentException(
                "Cannot withdraw {$quantity} of {$coin->name}, only {$available} available."
            );
        }

        $counts = $this->counts;
        $counts[$coin->value] = $available - $quantity;

        return new self($counts);
    }

    public function deposit(Coin $coin, int $quantity): self
    {
        $counts = $this->counts;
        $counts[$coin->value] = $this->quantityOf($coin) + $quantity;

        return new self($counts);
    }

    /** @return array<int, int> Coin value in cents => quantity available */
    public function toArray(): array
    {
        return $this->counts;
    }
}
