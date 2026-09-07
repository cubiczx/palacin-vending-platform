<?php

declare(strict_types=1);

namespace App\Tests\Functional\Infrastructure\Http\Controller;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Model\TransactionLogEntry;
use App\Domain\Model\VendingMachine;
use App\Tests\Functional\FunctionalTestCase;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;

#[Group('functional')]
final class ServiceControllerTest extends FunctionalTestCase
{
    private function seedDefaultMachine(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [
                new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5),
                new Product(ProductSku::JUICE, 'Juice', Money::fromCents(100), 5),
                new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5),
            ],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));
    }

    public function testGetStateReturnsExactStockAndChangeInventory(): void
    {
        $this->seedDefaultMachine();

        $this->client->request('GET', '/api/service/state');

        self::assertResponseIsSuccessful();
        $body = $this->decodeJson();

        self::assertCount(3, $body['products']);
        $soda = current(array_filter($body['products'], static fn ($p) => $p['sku'] === 'SODA'));
        self::assertSame(5, $soda['stock']);
        self::assertSame(20, $body['changeInventory']['coins']['1.00']);
    }

    public function testRestockIncreasesStock(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/service/products/soda/restock',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['quantity' => 10]),
        );

        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/service/state');
        $body = $this->decodeJson();
        $soda = current(array_filter($body['products'], static fn ($p) => $p['sku'] === 'SODA'));
        self::assertSame(15, $soda['stock']);
    }

    public function testRestockingAnUnrecognizedSkuReturns404(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/service/products/cola/restock',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['quantity' => 10]),
        );

        self::assertResponseStatusCodeSame(404);
        $body = $this->decodeJson();
        self::assertSame('PRODUCT_NOT_FOUND', $body['error']);
    }

    public function testRestockingAProductNotInTheMachinesCatalogReturns404(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5)],
            changeInventory: ChangeInventory::fromCounts([]),
        ));

        $this->client->request(
            'POST',
            '/api/service/products/water/restock',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['quantity' => 10]),
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testUpdatePriceChangesPrice(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'PATCH',
            '/api/service/products/soda/price',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['price' => 1.75]),
        );

        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/service/state');
        $body = $this->decodeJson();
        $soda = current(array_filter($body['products'], static fn ($p) => $p['sku'] === 'SODA'));
        self::assertSame(1.75, $soda['price']);
    }

    public function testSetChangeInventoryReplacesTheWholeInventory(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/service/change',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['counts' => ['5' => 0, '10' => 0, '25' => 0, '100' => 3]]),
        );

        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/service/state');
        $body = $this->decodeJson();
        self::assertSame(
            ['0.05' => 0, '0.10' => 0, '0.25' => 0, '1.00' => 3],
            $body['changeInventory']['coins'],
        );
    }

    public function testTransactionsReturnsRecordedSales(): void
    {
        $this->seedDefaultMachine();
        $this->transactionLogs->record(new TransactionLogEntry(
            id: null,
            product: ProductSku::WATER,
            price: Money::fromCents(65),
            amountInserted: Money::fromCents(100),
            changeReturned: [25 => 1, 10 => 1],
            occurredAt: new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
        ));

        $this->client->request('GET', '/api/service/transactions');

        self::assertResponseIsSuccessful();
        $body = $this->decodeJson();
        self::assertSame(1, $body['total']);
        self::assertCount(1, $body['items']);
        self::assertSame('WATER', $body['items'][0]['product']);
    }

    public function testTransactionsReturnsEmptyWhenNoSalesRecorded(): void
    {
        $this->seedDefaultMachine();

        $this->client->request('GET', '/api/service/transactions');

        self::assertResponseIsSuccessful();
        $body = $this->decodeJson();
        self::assertSame(0, $body['total']);
        self::assertSame([], $body['items']);
    }

    public function testRestockingWithNegativeQuantityReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/service/products/soda/restock',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['quantity' => -1]),
        );

        self::assertResponseStatusCodeSame(400);
        $body = $this->decodeJson();
        self::assertSame('INVALID_RESTOCK_QUANTITY', $body['error']);
    }

    public function testSetChangeInventoryWithNegativeQuantityReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/service/change',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['counts' => ['25' => -5]]),
        );

        self::assertResponseStatusCodeSame(400);
        $body = $this->decodeJson();
        self::assertSame('INVALID_CHANGE_QUANTITY', $body['error']);
    }

    public function testSetChangeInventoryWithNegativeQuantityDoesNotChangeTheInventory(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/service/change',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['counts' => ['25' => -5]]),
        );

        $this->client->request('GET', '/api/service/state');
        $body = $this->decodeJson();
        self::assertSame(20, $body['changeInventory']['coins']['0.25']);
    }

    public function testTransactionsWithInvalidProductFilterReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request('GET', '/api/service/transactions?product=cola');

        self::assertResponseStatusCodeSame(400);
        $body = $this->decodeJson();
        self::assertSame('INVALID_PRODUCT_FILTER', $body['error']);
    }

    public function testTransactionsWithValidProductFilterReturnsOnlyMatchingEntries(): void
    {
        $this->seedDefaultMachine();
        $this->transactionLogs->record(new TransactionLogEntry(
            id: null,
            product: ProductSku::WATER,
            price: Money::fromCents(65),
            amountInserted: Money::fromCents(100),
            changeReturned: [25 => 1, 10 => 1],
            occurredAt: new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
        ));
        $this->transactionLogs->record(new TransactionLogEntry(
            id: null,
            product: ProductSku::SODA,
            price: Money::fromCents(150),
            amountInserted: Money::fromCents(150),
            changeReturned: [],
            occurredAt: new DateTimeImmutable('2026-08-24T11:00:00+00:00'),
        ));

        $this->client->request('GET', '/api/service/transactions?product=water');

        self::assertResponseIsSuccessful();
        $body = $this->decodeJson();
        self::assertSame(1, $body['total']);
        self::assertSame('WATER', $body['items'][0]['product']);
    }

    public function testUpdatingPriceToANegativeValueReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'PATCH',
            '/api/service/products/soda/price',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['price' => -1.5]),
        );

        self::assertResponseStatusCodeSame(400);
        $body = $this->decodeJson();
        self::assertSame('INVALID_PRODUCT_PRICE', $body['error']);
    }

    public function testUpdatingPriceToZeroIsAllowed(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'PATCH',
            '/api/service/products/soda/price',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['price' => 0.0]),
        );

        self::assertResponseStatusCodeSame(204);
    }
}
