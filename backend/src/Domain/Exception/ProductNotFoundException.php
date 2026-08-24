<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\ProductSku;

final class ProductNotFoundException extends DomainException
{

    public static function forSku(ProductSku $sku): self
    {
        return new self(sprintf('Product "%s" does not exist in this machine.', $sku->value));
    }

    public static function forUnknownSku(string $sku): self
    {
        return new self(sprintf('"%s" is not a recognized product.', $sku));
    }
}
