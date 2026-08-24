<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mongo;

use App\Domain\Model\ProductSku;
use App\Domain\Model\TransactionLogEntry;
use App\Domain\Model\TransactionLogFilter;
use App\Domain\Model\Money;
use App\Domain\Repository\TransactionLogRepositoryInterface;
use App\Infrastructure\Persistence\Mongo\Document\TransactionLogDocument;
use Doctrine\ODM\MongoDB\DocumentManager;

final readonly class TransactionLogRepository implements TransactionLogRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager,
    ) {
    }

    public function record(TransactionLogEntry $entry): void
    {
        $document = new TransactionLogDocument();
        $document->product = $entry->product->value;
        $document->priceCents = $entry->price->cents();
        $document->amountInsertedCents = $entry->amountInserted->cents();
        $document->changeReturned = $this->toStringKeyed($entry->changeReturned);
        $document->occurredAt = $entry->occurredAt;

        $this->documentManager->persist($document);
        $this->documentManager->flush();
    }

    public function search(TransactionLogFilter $filter): array
    {
        $qb = $this->documentManager->createQueryBuilder(TransactionLogDocument::class);

        if ($filter->from !== null) {
            $qb->field('occurredAt')->gte($filter->from);
        }
        if ($filter->to !== null) {
            $qb->field('occurredAt')->lte($filter->to);
        }
        if ($filter->product !== null) {
            $qb->field('product')->equals($filter->product->value);
        }

        $total = (clone $qb)->count()->getQuery()->execute();

        $documents = $qb
            ->sort('occurredAt', 'desc')
            ->skip(($filter->page - 1) * $filter->perPage)
            ->limit($filter->perPage)
            ->getQuery()
            ->execute();

        $items = [];
        foreach ($documents as $document) {
            $items[] = $this->toDomain($document);
        }

        return ['items' => $items, 'total' => $total];
    }

    private function toDomain(TransactionLogDocument $document): TransactionLogEntry
    {
        return new TransactionLogEntry(
            id: $document->id,
            product: ProductSku::from($document->product),
            price: Money::fromCents($document->priceCents),
            amountInserted: Money::fromCents($document->amountInsertedCents),
            changeReturned: array_map(intval(...), $this->fromStringKeyed($document->changeReturned)),
            occurredAt: $document->occurredAt,
        );
    }

    /**
     * @param array<int, int> $counts
     * @return array<string, int>
    */
    private function toStringKeyed(array $counts): array
    {
        $result = [];
        foreach ($counts as $cents => $quantity) {
            $result[(string) $cents] = $quantity;
        }

        return $result;
    }

    /**
     * @param array<string, int> $counts
     * @return array<int, int>
     */
    private function fromStringKeyed(array $counts): array
    {
        $result = [];
        foreach ($counts as $cents => $quantity) {
            $result[(int) $cents] = $quantity;
        }

        return $result;
    }
}
