<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Event\ProductVendedEvent;
use App\Domain\Exception\ExactChangeUnavailableException;
use App\Domain\Exception\InsufficientFundsException;
use App\Domain\Exception\OutOfStockException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Service\ChangeCalculator;
use DateTimeImmutable;

/**
 * Aggregate root modelling a single physical vending machine.
 *
 * Consistency boundary: stock, change inventory and the currently
 * inserted amount are always modified together, atomically, from the
 * customer's point of view — this is what makes it a natural fit for a
 * single MongoDB document (see Infrastructure/Persistence/Mongo).
 *
 * Design decision: change is always computed from the machine's
 * *existing* change inventory, never from the coins the customer just
 * inserted in the current session. Inserted coins are deposited into the
 * change inventory only *after* change has been successfully withdrawn
 * from it. This keeps change-making deterministic at the cost of
 * occasionally rejecting a purchase the machine could technically
 * complete by "reusing" a just-inserted coin — a documented trade-off.
 */
final class VendingMachine
{
    /** @var array<string, Product> Indexed by ProductSku::value */
    private array $products;

    /** @var list<Coin> Coins inserted in the current, uncommitted session */
    private array $insertedCoins = [];

    private Money $insertedAmount;

    /** @var list<ProductVendedEvent> */
    private array $recordedEvents = [];

    /** @param list<Product> $products */
    private function __construct(
        private readonly string $id,
        array $products,
        private ChangeInventory $changeInventory,
    ) {
        $this->products = [];
        foreach ($products as $product) {
            $this->products[$product->sku()->value] = $product;
        }

        $this->insertedAmount = Money::zero();
    }

    /** @param list<Product> $products */
    public static function create(string $id, array $products, ChangeInventory $changeInventory): self
    {
        return new self($id, $products, $changeInventory);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function insertCoin(Coin $coin): void
    {
        $this->insertedCoins[] = $coin;
        $this->insertedAmount = $this->insertedAmount->add($coin->asMoney());
    }

    /**
     * @throws ProductNotFoundException
     * @throws OutOfStockException
     * @throws InsufficientFundsException
     * @throws ExactChangeUnavailableException
     */
    public function selectProduct(
        ProductSku $sku,
        ChangeCalculator $changeCalculator,
        DateTimeImmutable $now,
    ): VendingResult {
        $product = $this->productOrFail($sku);

        if (!$product->isInStock()) {
            throw OutOfStockException::forProduct($sku);
        }

        if (!$this->insertedAmount->isGreaterThanOrEqualTo($product->price())) {
            throw InsufficientFundsException::forShortfall($this->insertedAmount, $product->price());
        }

        $changeDue = $this->insertedAmount->subtract($product->price());
        $changeCoins = $changeCalculator->calculate($changeDue, $this->changeInventory);

        foreach ($changeCoins as $cents => $quantity) {
            $this->changeInventory = $this->changeInventory->withdraw(Coin::from($cents), $quantity);
        }

        foreach ($this->insertedCoins as $coin) {
            $this->changeInventory = $this->changeInventory->deposit($coin, 1);
        }

        $product->decrementStock();

        $this->recordedEvents[] = new ProductVendedEvent(
            product: $sku,
            price: $product->price(),
            amountInserted: $this->insertedAmount,
            changeReturned: $changeCoins,
            occurredAt: $now,
        );

        $result = new VendingResult($sku, $changeCoins);

        $this->resetSession();

        return $result;
    }

    /** @return array<int, int> Coin value in cents => quantity returned */
    public function returnCoins(): array
    {
        $returned = [];
        foreach ($this->insertedCoins as $coin) {
            $returned[$coin->value] = ($returned[$coin->value] ?? 0) + 1;
        }

        $this->resetSession();

        return $returned;
    }

    public function restockProduct(ProductSku $sku, int $quantity): void
    {
        $this->productOrFail($sku)->restock($quantity);
    }

    public function updateProductPrice(ProductSku $sku, Money $newPrice): void
    {
        $this->productOrFail($sku)->changePrice($newPrice);
    }

    public function setChangeInventory(ChangeInventory $inventory): void
    {
        $this->changeInventory = $inventory;
    }

    /** @return list<Product> */
    public function products(): array
    {
        return array_values($this->products);
    }

    public function changeInventory(): ChangeInventory
    {
        return $this->changeInventory;
    }

    public function insertedAmount(): Money
    {
        return $this->insertedAmount;
    }

    /** @return list<ProductVendedEvent> */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function productOrFail(ProductSku $sku): Product
    {
        return $this->products[$sku->value] ?? throw ProductNotFoundException::forSku($sku);
    }

    private function resetSession(): void
    {
        $this->insertedAmount = Money::zero();
        $this->insertedCoins = [];
    }
}
