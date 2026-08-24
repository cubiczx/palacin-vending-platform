<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Query;

use App\Application\Query\GetTransactionHistoryQuery;
use App\Application\Query\GetTransactionHistoryQueryHandler;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Domain\Model\TransactionLogEntry;
use App\Tests\Support\InMemoryTransactionLogRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class GetTransactionHistoryQueryHandlerTest extends TestCase
{
    public function testDelegatesToTheRepositoryAndReturnsItsResult(): void
    {
        $repository = new InMemoryTransactionLogRepository();
        $repository->record(new TransactionLogEntry(
            id: null,
            product: ProductSku::WATER,
            price: Money::fromCents(65),
            amountInserted: Money::fromCents(100),
            changeReturned: [25 => 1, 10 => 1],
            occurredAt: new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
        ));
        $handler = new GetTransactionHistoryQueryHandler($repository);

        $result = $handler(new GetTransactionHistoryQuery());

        self::assertSame(1, $result['total']);
        self::assertCount(1, $result['items']);
        self::assertSame(ProductSku::WATER, $result['items'][0]->product);
    }

    public function testReturnsEmptyResultWhenNoTransactionsExist(): void
    {
        $handler = new GetTransactionHistoryQueryHandler(new InMemoryTransactionLogRepository());

        $result = $handler(new GetTransactionHistoryQuery());

        self::assertSame(0, $result['total']);
        self::assertSame([], $result['items']);
    }

    public function testBuildsAFilterFromTheQueryParameters(): void
    {
        // InMemoryTransactionLogRepository::search() ignores the filter
        // content and returns everything (it's a simple test double), so
        // this test only asserts the handler constructs a well-formed
        // TransactionLogFilter from the query without error — filter
        // *behaviour* (date range, product matching) belongs to the
        // repository implementation's own test suite (integration level),
        // not here.
        $handler = new GetTransactionHistoryQueryHandler(new InMemoryTransactionLogRepository());

        $result = $handler(new GetTransactionHistoryQuery(
            from: new DateTimeImmutable('2026-08-01T00:00:00+00:00'),
            to: new DateTimeImmutable('2026-08-31T23:59:59+00:00'),
            product: ProductSku::SODA,
            page: 2,
            perPage: 10,
        ));

        self::assertIsArray($result['items']);
        self::assertIsInt($result['total']);
    }
}
