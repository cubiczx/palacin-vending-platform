<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidProductPriceException extends DomainException
{
    public static function forNegativePrice(float $price): self
    {
        return new self(sprintf('Product price cannot be negative, got %.2f.', $price));
    }
}
