<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mongo;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingMachine;
use App\Domain\Repository\VendingMachineRepositoryInterface;
use App\Infrastructure\Persistence\Mongo\Document\VendingMachineDocument;
use Doctrine\ODM\MongoDB\DocumentManager;

final readonly class VendingMachineRepository implements VendingMachineRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager,
    ) {
    }

    public function find(string $id): ?VendingMachine
    {
        $document = $this->documentManager->find(VendingMachineDocument::class, $id);

        return $document instanceof VendingMachineDocument
            ? $this->toDomain($document)
            : null;
    }

    public function save(VendingMachine $machine): void
    {
        $document = $this->documentManager->find(VendingMachineDocument::class, $machine->id())
            ?? new VendingMachineDocument();

        $document->id = $machine->id();
        $document->products = array_map(
            static fn (Product $product): array => [
                'sku' => $product->sku()->value,
                'name' => $product->name(),
                'priceCents' => $product->price()->cents(),
                'stock' => $product->stock(),
            ],
            $machine->products(),
        );
        $document->changeInventory = $this->toStringKeyed($machine->changeInventory()->toArray());

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    private function toDomain(VendingMachineDocument $document): VendingMachine
    {
        $products = array_map(
            static fn (array $row): Product => new Product(
                sku: ProductSku::from($row['sku']),
                name: $row['name'],
                price: Money::fromCents($row['priceCents']),
                stock: $row['stock'],
            ),
            $document->products,
        );

        $changeInventory = ChangeInventory::fromCounts(
            array_map(intval(...), $this->fromStringKeyed($document->changeInventory)),
        );

        return VendingMachine::create($document->id, $products, $changeInventory);
    }

    /**
     * @param array<int, int> $counts Coin cents => quantity
     * @return array<string, int>
     */
    private function toStringKeyed(array $counts): array
    {
        /** @var array<string, int> $result */
        $result = [];
        foreach ($counts as $cents => $quantity) {
            $result[(string) $cents] = $quantity;
        }

        return $result;
    }

    /**
     * @param array<string, int> $counts
     * @return array<int, int>
     */
    private function fromStringKeyed(array $counts): array
    {
        /** @var array<int, int> $result */
        $result = [];
        foreach ($counts as $cents => $quantity) {
            $result[(int) $cents] = $quantity;
        }

        return $result;
    }
}
