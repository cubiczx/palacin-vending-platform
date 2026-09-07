<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mongo\Document;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(collection: 'transaction_logs')]
class TransactionLogDocument
{
    #[ODM\Id]
    public ?string $id = null;

    #[ODM\Field(type: 'string')]
    public string $product;

    #[ODM\Field(type: 'int')]
    public int $priceCents;

    #[ODM\Field(type: 'int')]
    public int $amountInsertedCents;

    /** @var array<string, int> Coin value in cents (as string key) => quantity */
    #[ODM\Field(type: 'raw')]
    public array $changeReturned = [];

    #[ODM\Field(type: 'date_immutable')]
    public DateTimeImmutable $occurredAt;
}
