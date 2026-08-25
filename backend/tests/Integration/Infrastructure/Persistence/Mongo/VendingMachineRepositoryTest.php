<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Mongo;

use App\Domain\Model\ProductSku;
use App\Infrastructure\Persistence\Mongo\Document\VendingMachineDocument;
use App\Infrastructure\Persistence\Mongo\VendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('integration')]
final class VendingMachineRepositoryTest extends KernelTestCase
{
    private DocumentManager $dm;
    private VendingMachineRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dm = self::getContainer()->get(DocumentManager::class);
        $this->repository = new VendingMachineRepository($this->dm);

        $this->dm->getDocumentCollection(VendingMachineDocument::class)->deleteMany([]);
        $this->dm->clear();
    }

    public function testSaveAndFindVendingMachine(): void
    {
        $machine = VendingMachineFixture::withDefaultCatalog(sodaStock: 2);
        // If your fixture does not accept IDs, create it like this:
        $machine = \App\Domain\Model\VendingMachine::create(
            id: 'integration-test-01',
            products: $machine->products(),
            changeInventory: $machine->changeInventory()
        );

        $this->repository->save($machine);
        $this->dm->clear(); // You have to read from Mongo, not from memory.

        $found = $this->repository->find('integration-test-01');

        self::assertNotNull($found);
        self::assertSame('integration-test-01', $found->id());
        self::assertCount(3, $found->products());
        self::assertSame(
            $machine->changeInventory()->toArray(),
            $found->changeInventory()->toArray()
        );
    }

    public function testFindReturnsNullWhenNotFound(): void
    {
        self::assertNull($this->repository->find('does-not-exist'));
    }

    public function testConcurrentSavesFromTwoIndependentRequestsDetectVersionConflict(): void
    {
        $original = VendingMachineFixture::withDefaultCatalog(id: 'integration-test-04', sodaStock: 5);
        $this->repository->save($original);
        $this->dm->clear();

        // Two fully independent DocumentManagers (own identity maps), the
        // way each of two concurrent PHP-FPM requests would have in
        // production — clear() on a shared $this->dm is NOT enough to
        // simulate this, since it doesn't stop a later save() from picking
        // up the other "request"'s already-flushed, already-updated object.
        $dmRequestA = DocumentManager::create($this->dm->getClient(), $this->dm->getConfiguration());
        $dmRequestB = DocumentManager::create($this->dm->getClient(), $this->dm->getConfiguration());
        $repositoryA = new VendingMachineRepository($dmRequestA);
        $repositoryB = new VendingMachineRepository($dmRequestB);

        $requestA = $repositoryA->find('integration-test-04');
        $requestB = $repositoryB->find('integration-test-04');

        $requestA->restockProduct(ProductSku::SODA, 3);
        $repositoryA->save($requestA); // succeeds: DB version 1 -> 2

        $requestB->restockProduct(ProductSku::SODA, 100);

        $this->expectException(LockException::class);
        $repositoryB->save($requestB); // stale version 1 in memory, DB now at 2 -> conflict
    }
}
