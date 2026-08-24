<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mongo\Document;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(collection: 'machine_state')]
class VendingMachineDocument
{
    #[ODM\Id(strategy: 'NONE', type: 'string')]
    public string $id;

    /** @var list<array{sku: string, name: string, priceCents: int, stock: int}> */
    #[ODM\Field(type: 'raw')]
    public array $products = [];

    /** @var array<string, int> Coin value in cents (as string key) => quantity */
    #[ODM\Field(type: 'raw')]
    public array $changeInventory = [];

    #[ODM\Version]
    #[ODM\Field(type: 'int')]
    public int $version = 1;
}
