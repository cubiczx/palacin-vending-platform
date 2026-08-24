<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Exception\InvalidCoinException;

/**
 * The finite set of denominations accepted and dispensed by the machine.
 * Backed by their value in cents so ordering/comparison is exact.
 */
enum Coin: int
{
    case FIVE_CENTS = 5;
    case TEN_CENTS = 10;
    case TWENTY_FIVE_CENTS = 25;
    case ONE_EURO = 100;

    public function asMoney(): Money
    {
        return Money::fromCents($this->value);
    }

    public static function fromCentsOrFail(int $cents): self
    {
        return self::tryFrom($cents) ?? throw InvalidCoinException::forCents($cents);
    }

    /**
     * All accepted denominations, highest to lowest value. Used by the
     * greedy change-making algorithm in ChangeCalculator.
     *
     * @return list<self>
     */
    public static function allDescending(): array
    {
        return [self::ONE_EURO, self::TWENTY_FIVE_CENTS, self::TEN_CENTS, self::FIVE_CENTS];
    }
}
