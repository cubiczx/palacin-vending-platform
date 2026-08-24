<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Money;

final class InsufficientFundsException extends DomainException
{
    public static function forShortfall(Money $inserted, Money $price): self
    {
        return new self(sprintf(
            'Insufficient funds: inserted %.2f, price is %.2f.',
            $inserted->toEuros(),
            $price->toEuros(),
        ));
    }
}
