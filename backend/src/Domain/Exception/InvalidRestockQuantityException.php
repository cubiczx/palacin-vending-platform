<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidRestockQuantityException extends DomainException
{
    public static function forNegativeQuantity(): self
    {
        return new self('Restock quantity cannot be negative.');
    }
}
