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

#[Group('functional')]
final class RequestBodyExceptionListenerTest extends FunctionalTestCase
{
    private function seedDefaultMachine(): void
    {
        $this->machines->save(VendingMachine::create(
            id: 'machine-01',
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));
    }

    private function assertInvalidRequestBodyResponse(): void
    {
        self::assertResponseStatusCodeSame(400);

        $body = $this->decodeJson();
        self::assertArrayHasKey('error', $body);
        self::assertArrayHasKey('message', $body);
        self::assertSame('INVALID_REQUEST_BODY', $body['error']);
        self::assertNotEmpty($body['message']);
    }

    public function testMalformedJsonMapsTo400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"cents": a}',
        );

        $this->assertInvalidRequestBodyResponse();
    }

    public function testWrongFieldTypeMapsTo400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['cents' => 'a']),
        );

        $this->assertInvalidRequestBodyResponse();
    }

    public function testMissingRequiredFieldMapsTo400(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([]),
        );

        $this->assertInvalidRequestBodyResponse();
    }

    public function testMalformedJsonDoesNotChangeInsertedAmount(): void
    {
        $this->seedDefaultMachine();

        $this->client->request(
            'POST',
            '/api/machine/coins',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"cents": a}',
        );

        $this->client->request('GET', '/api/machine/state');
        $body = $this->decodeJson();
        self::assertSame(0.0, $body['insertedAmount']);
    }
}
