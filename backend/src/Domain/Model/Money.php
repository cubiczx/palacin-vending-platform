<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Immutable value object representing a monetary amount.
 *
 * Stored internally as integer cents to avoid floating-point rounding
 * errors inherent to float arithmetic on currency (e.g. 0.1 + 0.2 !== 0.3
 * in IEEE-754). toEuros()/fromEuros() exist only as a serialization
 * boundary to/from the HTTP layer — all internal arithmetic stays in cents.
 */
final readonly class Money
{
    private function __construct(private int $cents)
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromEuros(float $euros): self
    {
        return new self((int) round($euros * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function toEuros(): float
    {
        return $this->cents / 100;
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->cents >= $other->cents;
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents;
    }
}
