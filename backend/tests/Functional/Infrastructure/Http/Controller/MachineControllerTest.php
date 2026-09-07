<?php

declare(strict_types=1);

namespace App\Tests\Functional\Infrastructure\Http\Controller;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingMachine;
use App\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('functional')]
final class MachineControllerTest extends FunctionalTestCase
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

    public function testGetStateReturnsCatalogWithZeroBalance(): void
    {
        $this->seedDefaultMachine();

        $this->client->request('GET', '/api/machine/state');

        self::assertResponseIsSuccessful();
        /** @var array{products: array<mixed>, insertedAmount: float} $body */
        $body = $this->decodeJson();

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
            content: $this->jsonBody(['cents' => 25]),
        );

        self::assertResponseIsSuccessful();
        /** @var array{insertedAmount: float} $body */
        $body = $this->decodeJson();
        self::assertSame(0.25, $body['insertedAmount']);
    }

    public function testInsertingAnInvalidCoinDenominationReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['cents' => 2]),
        );

        self::assertResponseStatusCodeSame(400);
        /** @var array{error: string} $body */
        $body = $this->decodeJson();
        self::assertSame('INVALID_COIN', $body['error']);
    }

    public function testInsertingANonNumericCentsValueReturns400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['cents' => 'a']),
        );

        self::assertResponseStatusCodeSame(400);
        /** @var array{error: string} $body */
        $body = $this->decodeJson();
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
        /** @var array{error: string} $body */
        $body = $this->decodeJson();
        self::assertSame('INVALID_REQUEST_BODY', $body['error']);
    }

    public function testExample1BuySodaWithExactChangeReturnsNoCoins(): void
    {
        $this->seedDefaultMachine();

        foreach ([100, 25, 25] as $cents) {
            $this->client->request(
                'POST',
                '/api/machine/coins',
                server: ['CONTENT_TYPE' => 'application/json'],
                content: $this->jsonBody(['cents' => $cents]),
            );
        }

        $this->client->request('POST', '/api/machine/select/soda');

        self::assertResponseIsSuccessful();
        /** @var array{product: string, change: array{coins: array<mixed>}} $body */
        $body = $this->decodeJson();
        self::assertSame('SODA', $body['product']);
        self::assertSame([], $body['change']['coins']);
    }

    public function testExample2InsertCoinsThenReturnCoinGivesThemBack(): void
    {
        $this->seedDefaultMachine();

        foreach ([10, 10] as $cents) {
            $this->client->request(
                'POST',
                '/api/machine/coins',
                server: ['CONTENT_TYPE' => 'application/json'],
                content: $this->jsonBody(['cents' => $cents]),
            );
        }

        $this->client->request('POST', '/api/machine/return');

        self::assertResponseIsSuccessful();
        /** @var array{coins: array<string, int>} $body */
        $body = $this->decodeJson();
        self::assertSame(['0.10' => 2], $body['coins']);
    }

    public function testExample3BuyWaterWithoutExactChangeReturnsChange(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['cents' => 100]),
        );

        $this->client->request('POST', '/api/machine/select/water');

        self::assertResponseIsSuccessful();
        /** @var array{product: string, change: array{coins: array<string, int>}} $body */
        $body = $this->decodeJson();
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
            content: $this->jsonBody(['cents' => 25]),
        );

        $this->client->request('POST', '/api/machine/select/soda');

        self::assertResponseStatusCodeSame(402);
        /** @var array{error: string} $body */
        $body = $this->decodeJson();
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
            content: $this->jsonBody(['cents' => 100]),
        );
        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $this->jsonBody(['cents' => 100]),
        );

        $this->client->request('POST', '/api/machine/select/soda');

        self::assertResponseStatusCodeSame(409);
        /** @var array{error: string} $body */
        $body = $this->decodeJson();
        self::assertSame('OUT_OF_STOCK', $body['error']);
    }

    public function testSelectingAProductNotInTheMachinesCatalogReturns404(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));

        $this->client->request('POST', '/api/machine/select/water');

        self::assertResponseStatusCodeSame(404);
        /** @var array{error: string} $body */
        $body = $this->decodeJson();
        self::assertSame('PRODUCT_NOT_FOUND', $body['error']);
    }

    public function testSelectingAnUnrecognizedSkuReturns404(): void
    {
        $this->seedDefaultMachine();

        $this->client->request('POST', '/api/machine/select/cola');

        self::assertResponseStatusCodeSame(404);
        /** @var array{error: string} $body */
        $body = $this->decodeJson();
        self::assertSame('PRODUCT_NOT_FOUND', $body['error']);
    }
}
