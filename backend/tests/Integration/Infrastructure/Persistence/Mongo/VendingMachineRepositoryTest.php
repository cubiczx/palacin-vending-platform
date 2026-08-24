<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Mongo;

use App\Infrastructure\Persistence\Mongo\Document\VendingMachineDocument;
use App\Infrastructure\Persistence\Mongo\VendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use Doctrine\ODM\MongoDB\DocumentManager;
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
}
