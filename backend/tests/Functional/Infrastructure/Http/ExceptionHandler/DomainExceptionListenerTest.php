<?php

declare(strict_types=1);

namespace App\Tests\Functional\Infrastructure\Http\ExceptionHandler;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingMachine;
use App\Domain\Repository\VendingMachineRepositoryInterface;
use App\Infrastructure\Persistence\Mongo\Document\TransactionLogDocument;
use App\Infrastructure\Persistence\Mongo\Document\VendingMachineDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Consolidates coverage of every domain exception -> HTTP response mapping
 * performed by DomainExceptionListener, so the full mapping table is
 * verified in one place rather than duplicated piecemeal across
 * MachineControllerTest and ServiceControllerTest.
 */
#[Group('functional')]
final class DomainExceptionListenerTest extends WebTestCase
{
    private KernelBrowser $client;
    private VendingMachineRepositoryInterface $machines;
    private DocumentManager $documentManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $container = $this->client->getContainer();
        $this->machines = $container->get(VendingMachineRepositoryInterface::class);
        $this->documentManager = $container->get(DocumentManager::class);

        $this->documentManager->getDocumentCollection(VendingMachineDocument::class)->deleteMany([]);
        $this->documentManager->getDocumentCollection(TransactionLogDocument::class)->deleteMany([]);
    }

    private function assertDomainErrorResponse(string $expectedErrorCode, int $expectedStatus): void
    {
        self::assertResponseStatusCodeSame($expectedStatus);

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
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

        // WATER is a valid enum case but this machine's catalog only has SODA.
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
        // Water costs 0.65; paying with a 1 EUR coin requires 0.35 change,
        // but the machine's change inventory is completely empty.
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

        // Verify the customer's money and the machine's stock are untouched
        // by inspecting state through the public read endpoints, rather than
        // reaching into the repository directly.
        $this->client->request('GET', '/api/machine/state');
        $stateBody = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(1.0, $stateBody['insertedAmount']);

        $this->client->request('GET', '/api/service/state');
        $serviceBody = json_decode((string) $this->client->getResponse()->getContent(), true);
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
