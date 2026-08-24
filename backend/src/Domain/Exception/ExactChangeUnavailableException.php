<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\Money;

final class ExactChangeUnavailableException extends DomainException
{
    public static function forAmount(Money $amount): self
    {
        return new self(sprintf(
            'The machine cannot return exact change for %.2f with the coins currently available.',
            $amount->toEuros(),
        ));
    }
}
