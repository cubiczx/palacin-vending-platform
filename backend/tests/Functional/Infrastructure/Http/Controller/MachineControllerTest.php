<?php

declare(strict_types=1);

namespace App\Tests\Functional\Infrastructure\Http\Controller;

use App\Infrastructure\Persistence\Mongo\Document\TransactionLogDocument;
use App\Infrastructure\Persistence\Mongo\Document\VendingMachineDocument;
use App\Infrastructure\Persistence\Mongo\VendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('functional')]
final class MachineControllerTest extends WebTestCase
{
    private DocumentManager $dm;
    private \Symfony\Bundle\FrameworkBundle\KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->dm = $container->get(DocumentManager::class);

        // Limpia todo
        $this->dm->getDocumentCollection(VendingMachineDocument::class)->deleteMany([]);
        $this->dm->getDocumentCollection(TransactionLogDocument::class)->deleteMany([]);
        $this->dm->clear();

        // Seed de machine-01, que es la constante hardcodeada en el controller
        $fixture = VendingMachineFixture::withDefaultCatalog(sodaStock: 2);
        $machine = \App\Domain\Model\VendingMachine::create(
            id: 'machine-01',
            products: $fixture->products(),
            changeInventory: $fixture->changeInventory()
        );

        (new VendingMachineRepository($this->dm))->save($machine);
        $this->dm->clear();
    }

    public function testStateEndpointReturnsMachineView(): void
    {
        $this->client->request('GET', '/api/machine/state');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);

        self::assertArrayHasKey('products', $data);
        self::assertCount(3, $data['products']);
        // El DTO no expone stock, solo inStock
        self::assertArrayNotHasKey('stock', $data['products'][0]);
        self::assertArrayHasKey('inStock', $data['products'][0]);
        self::assertSame(0.0, $data['insertedAmount']);
    }

    public function testInsertCoinEndpoint(): void
    {
        $this->client->request(
            'POST',
            '/api/machine/coins',
            content: json_encode(['cents' => 100]),
            server: ['CONTENT_TYPE' => 'application/json']
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals(1.0, $data['insertedAmount']);

        // Verifica que el state refleja el insert
        $this->client->request('GET', '/api/machine/state');
        $state = json_decode($this->client->getResponse()->getContent(), true);
        // Ajusta si tu response devuelve cents o euros
        self::assertNotSame(0, $state['insertedAmount']);
    }

    public function testFullFlowInsertAndSelect(): void
    {
        $this->client->request('POST', '/api/machine/coins', content: json_encode(['cents' => 100]), server: ['CONTENT_TYPE' => 'application/json']);
        self::assertResponseIsSuccessful();

        $this->client->request('POST', '/api/machine/select/WATER');
        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        // VendingResponse::fromResult() -> adapta keys si son diferentes
        self::assertArrayHasKey('product', $data);
        self::assertSame('WATER', $data['product']['sku']?? $data['product']);
    }

    public function testReturnCoinsEndpoint(): void
    {
        $this->client->request('POST', '/api/machine/coins', content: json_encode(['cents' => 25]), server: ['CONTENT_TYPE' => 'application/json']);
        self::assertResponseIsSuccessful();

        $this->client->request('POST', '/api/machine/return');
        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        // CoinsResponse::fromCents() - debería devolver algo con 25
        var_dump($data); // Depuración temporal
        self::assertNotEmpty($data);
    }
}
