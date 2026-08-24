<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use App\Domain\Model\ProductSku;

final class OutOfStockException extends DomainException
{
    public static function forProduct(ProductSku $sku): self
    {
        return new self(sprintf('Product "%s" is out of stock.', $sku->value));
    }
}
