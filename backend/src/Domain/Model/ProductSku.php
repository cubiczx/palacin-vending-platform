<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * Identifies a purchasable product. String-backed so it serializes
 * naturally to/from the HTTP layer (e.g. "SODA") without a lookup table.
 */
enum ProductSku: string
{
    case WATER = 'WATER';
    case JUICE = 'JUICE';
    case SODA = 'SODA';
}
