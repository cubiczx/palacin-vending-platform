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
 * Consolidates coverage of RequestBodyExceptionListener, which maps
 * malformed/invalid request bodies caught by the serializer to a 400
 * response — as opposed to DomainExceptionListener, which maps business
 * rule violations. Kept in its own file since the two listeners handle
 * distinct exception hierarchies for distinct reasons.
 */
#[Group('functional')]
final class RequestBodyExceptionListenerTest extends WebTestCase
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
            products: [new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5)],
            changeInventory: ChangeInventory::fromCounts([5 => 20, 10 => 20, 25 => 20, 100 => 20]),
        ));
    }

    private function assertInvalidRequestBodyResponse(): void
    {
        self::assertResponseStatusCodeSame(400);

        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
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
        $body = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertSame(0.0, $body['insertedAmount']);
    }
}
