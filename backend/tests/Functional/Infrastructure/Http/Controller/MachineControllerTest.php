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
            content: json_encode(['cents' => 25]),
        );

        self::assertResponseIsSuccessful();
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
            content: json_encode(['cents' => 2]),
        );

        self::assertResponseStatusCodeSame(400);
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
            content: json_encode(['cents' => 'a']),
        );

        self::assertResponseStatusCodeSame(400);
        $body = $this->decodeJson();
