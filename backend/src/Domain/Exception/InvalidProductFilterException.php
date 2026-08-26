<?php

declare(strict_types=1);

namespace App\Domain\Exception;

final class InvalidProductFilterException extends DomainException
{
    public static function forUnknownSku(string $sku): self
    {
        return new self(sprintf('"%s" is not a recognized product.', $sku));
    }
}
