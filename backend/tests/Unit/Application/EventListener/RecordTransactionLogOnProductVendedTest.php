<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\EventListener;

use App\Application\EventListener\RecordTransactionLogOnProductVended;
use App\Domain\Event\ProductVendedEvent;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Tests\Support\InMemoryTransactionLogRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RecordTransactionLogOnProductVendedTest extends TestCase
{
    public function testRecordsATransactionLogEntryMatchingTheEvent(): void
    {
        $repository = new InMemoryTransactionLogRepository();
        $listener = new RecordTransactionLogOnProductVended($repository);

        $occurredAt = new DateTimeImmutable('2026-08-24T10:00:00+00:00');
        $event = new ProductVendedEvent(
            product: ProductSku::WATER,
            price: Money::fromCents(65),
            amountInserted: Money::fromCents(100),
            changeReturned: [25 => 1, 10 => 1],
            occurredAt: $occurredAt,
        );

        $listener($event);

        $entries = $repository->all();
        self::assertCount(1, $entries);

        $entry = $entries[0];
        self::assertSame(ProductSku::WATER, $entry->product);
        self::assertSame(65, $entry->price->cents());
        self::assertSame(100, $entry->amountInserted->cents());
        self::assertSame([25 => 1, 10 => 1], $entry->changeReturned);
        self::assertSame($occurredAt, $entry->occurredAt);
        self::assertNull($entry->id, 'id is assigned by the persistence layer, not the listener');
    }

    public function testEachInvocationRecordsASeparateEntry(): void
    {
        $repository = new InMemoryTransactionLogRepository();
        $listener = new RecordTransactionLogOnProductVended($repository);

        $listener(new ProductVendedEvent(
            product: ProductSku::WATER,
            price: Money::fromCents(65),
            amountInserted: Money::fromCents(65),
            changeReturned: [],
            occurredAt: new DateTimeImmutable('2026-08-24T10:00:00+00:00'),
        ));
        $listener(new ProductVendedEvent(
            product: ProductSku::SODA,
            price: Money::fromCents(150),
            amountInserted: Money::fromCents(150),
            changeReturned: [],
            occurredAt: new DateTimeImmutable('2026-08-24T10:05:00+00:00'),
        ));

        self::assertCount(2, $repository->all());
    }
}
