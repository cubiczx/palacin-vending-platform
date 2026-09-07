<?php

declare(strict_types=1);

namespace App\Tests\Functional\Infrastructure\Http\ExceptionHandler;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingMachine;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Consolidates coverage of every domain exception -> HTTP response mapping
 * performed by DomainExceptionListener, so the full mapping table is
 * verified in one place rather than duplicated piecemeal across
 * MachineControllerTest and ServiceControllerTest.
 */
#[Group('functional')]
final class DomainExceptionListenerTest extends FunctionalTestCase
{
    private function assertDomainErrorResponse(string $expectedErrorCode, int $expectedStatus): void
    {
        self::assertResponseStatusCodeSame($expectedStatus);

        $body = $this->decodeJson();
        self::assertArrayHasKey('error', $body);
        self::assertArrayHasKey('message', $body);
        self::assertSame($expectedErrorCode, $body['error']);
        self::assertNotEmpty($body['message']);
    }

    public function testInvalidCoinMapsTo400(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 2]),
        );

        $this->assertDomainErrorResponse('INVALID_COIN', 400);
    }

    public function testProductNotFoundMapsTo404(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request('POST', '/api/machine/select/water');

        $this->assertDomainErrorResponse('PRODUCT_NOT_FOUND', 404);
    }

    public function testOutOfStockMapsTo409(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 0)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 100]),
        );
        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 100]),
        );

        $this->client->request('POST', '/api/machine/select/soda');

        $this->assertDomainErrorResponse('OUT_OF_STOCK', 409);
    }

    public function testInsufficientFundsMapsTo402(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 25]),
        );

        $this->client->request('POST', '/api/machine/select/soda');

        $this->assertDomainErrorResponse('INSUFFICIENT_FUNDS', 402);
    }

    public function testExactChangeUnavailableMapsTo409(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([]),
        ));

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 100]),
        );

        $this->client->request('POST', '/api/machine/select/water');

        $this->assertDomainErrorResponse('EXACT_CHANGE_UNAVAILABLE', 409);
    }

    public function testExactChangeUnavailableDoesNotChargeTheCustomerOrDecrementStock(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([]),
        ));

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 100]),
        );
        $this->client->request('POST', '/api/machine/select/water');

        $this->client->request('GET', '/api/machine/state');
        $stateBody = $this->decodeJson();
        self::assertSame(1.0, $stateBody['insertedAmount']);

        $this->client->request('GET', '/api/service/state');
        $serviceBody = $this->decodeJson();
        $water = current(array_filter($serviceBody['products'], static fn ($p) => $p['sku'] === 'WATER'));
        self::assertSame(5, $water['stock']);
    }

    public function testNegativeRestockQuantityMapsTo400(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request(
            'POST',
            '/api/service/products/water/restock',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['quantity' => -1]),
        );

        $this->assertDomainErrorResponse('INVALID_RESTOCK_QUANTITY', 400);
    }

    public function testNegativeChangeQuantityMapsTo400(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request(
            'POST',
            '/api/service/change',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['counts' => ['25' => -5]]),
        );

        $this->assertDomainErrorResponse('INVALID_CHANGE_QUANTITY', 400);
    }

    public function testInvalidProductFilterMapsTo400(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request('GET', '/api/service/transactions?product=cola');

        $this->assertDomainErrorResponse('INVALID_PRODUCT_FILTER', 400);
    }
}
