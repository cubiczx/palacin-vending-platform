<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Persistence\Mongo;

use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Domain\Model\TransactionLogEntry;
use App\Domain\Model\TransactionLogFilter;
use App\Infrastructure\Persistence\Mongo\Document\TransactionLogDocument;
use App\Infrastructure\Persistence\Mongo\TransactionLogRepository;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[Group('integration')]
final class TransactionLogRepositoryTest extends KernelTestCase
{
    private DocumentManager $dm;
    private TransactionLogRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->dm = self::getContainer()->get(DocumentManager::class);
        $this->repository = new TransactionLogRepository($this->dm);

        $this->dm->getDocumentCollection(TransactionLogDocument::class)->deleteMany([]);
        $this->dm->clear();
    }

    public function testRecordPersistsEntryWithCorrectMapping(): void
    {
        $at = new DateTimeImmutable('2026-08-24T10:00:00+00:00');
        $entry = new TransactionLogEntry(
            id: null,
            product: ProductSku::WATER,
            price: Money::fromCents(65),
            amountInserted: Money::fromCents(100),
            changeReturned: [25 => 1, 10 => 1], // int keys -> this is what fails in Mongo without toStringKeyed
            occurredAt: $at,
        );

        $this->repository->record($entry);
        $this->dm->clear();

        $result = $this->repository->search(new TransactionLogFilter());

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['items']);

        $found = $result['items'][0];
        self::assertSame(ProductSku::WATER, $found->product);
        self::assertSame(65, $found->price->cents());
        self::assertSame(100, $found->amountInserted->cents());
        self::assertSame([25 => 1, 10 => 1], $found->changeReturned);
        self::assertEquals($at, $found->occurredAt);
        self::assertNotNull($found->id);
    }

    public function testSearchSortsByOccurredAtDesc(): void
    {
        $this->recordEntry(ProductSku::SODA, '2026-08-24T09:00:00+00:00');
        $this->recordEntry(ProductSku::WATER, '2026-08-24T11:00:00+00:00');
        $this->dm->clear();

        $result = $this->repository->search(new TransactionLogFilter());

        self::assertSame(2, $result['total']);
        self::assertSame(ProductSku::WATER, $result['items'][0]->product); // the most recent first
        self::assertSame(ProductSku::SODA, $result['items'][1]->product);
    }

    public function testSearchFiltersByProduct(): void
    {
        $this->recordEntry(ProductSku::SODA, '2026-08-24T10:00:00+00:00');
        $this->recordEntry(ProductSku::WATER, '2026-08-24T10:00:00+00:00');
        $this->dm->clear();

        $result = $this->repository->search(new TransactionLogFilter(product: ProductSku::WATER));

        self::assertSame(1, $result['total']);
        self::assertSame(ProductSku::WATER, $result['items'][0]->product);
    }

    public function testSearchFiltersByDateRange(): void
    {
        $this->recordEntry(ProductSku::SODA, '2026-08-01T10:00:00+00:00');
        $this->recordEntry(ProductSku::SODA, '2026-08-15T10:00:00+00:00');
        $this->recordEntry(ProductSku::SODA, '2026-08-30T10:00:00+00:00');
        $this->dm->clear();

        $result = $this->repository->search(new TransactionLogFilter(
            from: new DateTimeImmutable('2026-08-10T00:00:00+00:00'),
            to: new DateTimeImmutable('2026-08-20T23:59:59+00:00'),
        ));

        self::assertSame(1, $result['total']);
        self::assertEquals(new DateTimeImmutable('2026-08-15T10:00:00+00:00'), $result['items'][0]->occurredAt);
    }

    public function testSearchPagination(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->recordEntry(ProductSku::JUICE, sprintf('2026-08-24T10:0%d:00+00:00', $i));
        }
        $this->dm->clear();

        $page1 = $this->repository->search(new TransactionLogFilter(page: 1, perPage: 2));
        $page2 = $this->repository->search(new TransactionLogFilter(page: 2, perPage: 2));
        $page3 = $this->repository->search(new TransactionLogFilter(page: 3, perPage: 2));

        self::assertSame(5, $page1['total']);
        self::assertCount(2, $page1['items']);
        self::assertCount(2, $page2['items']);
        self::assertCount(1, $page3['items']);
    }

    private function recordEntry(ProductSku $sku, string $at): void
    {
        $this->repository->record(new TransactionLogEntry(
            id: null,
            product: $sku,
            price: Money::fromCents(100),
            amountInserted: Money::fromCents(100),
            changeReturned: [],
            occurredAt: new DateTimeImmutable($at),
        ));
    }

    public function testSearchWithPageBeyondResultsReturnsEmptyItems(): void
    {
        $this->recordEntry(ProductSku::SODA, '2026-08-24T10:00:00+00:00');
        $this->dm->clear();

        $result = $this->repository->search(new TransactionLogFilter(page: 99, perPage: 20));

        self::assertSame(1, $result['total']);
        self::assertSame([], $result['items']);
    }
}
