<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidChangeQuantityException extends DomainException
{
    public static function forNegativeQuantity(string $coinName): self
    {
        return new self(sprintf('Coin quantity for %s cannot be negative.', $coinName));
    }
}
