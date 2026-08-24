<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Model\ProductSku;
use DateTimeImmutable;

final readonly class GetTransactionHistoryQuery
{
    public function __construct(
        public ?DateTimeImmutable $from = null,
        public ?DateTimeImmutable $to = null,
        public ?ProductSku $product = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
