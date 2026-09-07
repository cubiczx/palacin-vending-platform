<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Mongo;

use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingMachine;
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

        $dm = self::getContainer()->get(DocumentManager::class);
        assert($dm instanceof DocumentManager);
        $this->dm = $dm;

        $this->repository = new VendingMachineRepository($this->dm);
        $this->dm->getDocumentCollection(VendingMachineDocument::class)->deleteMany([]);
        $this->dm->clear();
    }

    public function testSaveAndFindVendingMachine(): void
    {
        $seed = VendingMachineFixture::withDefaultCatalog(sodaStock: 2);
        $machine = VendingMachine::create(
            id: 'integration-test-01',
            products: $seed->products(),
            changeInventory: $seed->changeInventory(),
        );

        $this->repository->save($machine);
        $this->dm->clear();

        $found = $this->repository->find('integration-test-01');

        self::assertNotNull($found);
        self::assertSame('integration-test-01', $found->id());
        self::assertCount(3, $found->products());
        self::assertSame(
            $machine->changeInventory()->toArray(),
            $found->changeInventory()->toArray(),
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

        $dmRequestA = DocumentManager::create($this->dm->getClient(), $this->dm->getConfiguration());
        $dmRequestB = DocumentManager::create($this->dm->getClient(), $this->dm->getConfiguration());
        $repositoryA = new VendingMachineRepository($dmRequestA);
        $repositoryB = new VendingMachineRepository($dmRequestB);

        $requestA = $repositoryA->find('integration-test-04');
        $requestB = $repositoryB->find('integration-test-04');
        self::assertNotNull($requestA);
        self::assertNotNull($requestB);

        $requestA->restockProduct(ProductSku::SODA, 3);
        $repositoryA->save($requestA);

        $requestB->restockProduct(ProductSku::SODA, 100);

        $this->expectException(LockException::class);
        $repositoryB->save($requestB);
    }
}
