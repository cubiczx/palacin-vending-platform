<?php

declare(strict_types=1);

namespace App\Tests\Functional\Infrastructure\Http\Controller;

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

#[Group('functional')]
final class MachineControllerTest extends WebTestCase
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

    public function testGetStateReturnsCatalogWithZeroBalance(): void
    {
        $this->seedDefaultMachine();

        $this->client->request('GET', '/api/machine/state');

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);

        self::assertCount(3, $body['products']);
        self::assertSame(0.0, $body['insertedAmount']);
    }

    public function testInsertCoinIncreasesInsertedAmount(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 25]),
        );

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(0.25, $body['insertedAmount']);
    }

    public function testInsertingAnInvalidCoinDenominationReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 2]),
        );

        self::assertResponseStatusCodeSame(400);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('INVALID_COIN', $body['error']);
    }

    public function testInsertingANonNumericCentsValueReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 'a']),
        );

        self::assertResponseStatusCodeSame(400);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('INVALID_REQUEST_BODY', $body['error']);
    }

    public function testInsertingMalformedJsonReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"cents": a}',
        );

        self::assertResponseStatusCodeSame(400);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('INVALID_REQUEST_BODY', $body['error']);
    }

    public function testExample1BuySodaWithExactChangeReturnsNoCoins(): void
    {
        // 1, 0.25, 0.25, GET-SODA -> SODA
        $this->seedDefaultMachine();

        foreach ([100, 25, 25] as $cents) {
            $this->client->request(
                'POST',
                '/api/machine/coins',
                server: ['CONTENT_TYPE' => 'application/json'],
                content: json_encode(['cents' => $cents]),
            );
        }

        $this->client->request('POST', '/api/machine/select/soda');

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('SODA', $body['product']);
        self::assertSame([], $body['change']['coins']);
    }

    public function testExample2InsertCoinsThenReturnCoinGivesThemBack(): void
    {
        // 0.10, 0.10, RETURN-COIN -> 0.10, 0.10
        $this->seedDefaultMachine();

        foreach ([10, 10] as $cents) {
            $this->client->request(
                'POST',
                '/api/machine/coins',
                server: ['CONTENT_TYPE' => 'application/json'],
                content: json_encode(['cents' => $cents]),
            );
        }

        $this->client->request('POST', '/api/machine/return');

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(['0.10' => 2], $body['coins']);
    }

    public function testExample3BuyWaterWithoutExactChangeReturnsChange(): void
    {
        // 1, GET-WATER -> WATER, 0.25, 0.10
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 100]),
        );

        $this->client->request('POST', '/api/machine/select/water');

        self::assertResponseIsSuccessful();
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('WATER', $body['product']);
        self::assertSame(['0.25' => 1, '0.10' => 1], $body['change']['coins']);
    }

    public function testSelectingWithInsufficientFundsReturns402(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 25]),
        );

        $this->client->request('POST', '/api/machine/select/soda');

        self::assertResponseStatusCodeSame(402);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('INSUFFICIENT_FUNDS', $body['error']);
    }

    public function testSelectingAnOutOfStockProductReturns409(): void
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

        self::assertResponseStatusCodeSame(409);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('OUT_OF_STOCK', $body['error']);
    }

    public function testSelectingAProductNotInTheMachinesCatalogReturns404(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        // WATER is a valid enum case but this machine's catalog only has SODA.
        $this->client->request('POST', '/api/machine/select/water');

        self::assertResponseStatusCodeSame(404);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('PRODUCT_NOT_FOUND', $body['error']);
    }

    public function testSelectingAnUnrecognizedSkuReturns404(): void
    {
        $this->seedDefaultMachine();

        $this->client->request('POST', '/api/machine/select/cola');

        self::assertResponseStatusCodeSame(404);
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame('PRODUCT_NOT_FOUND', $body['error']);
    }
}
