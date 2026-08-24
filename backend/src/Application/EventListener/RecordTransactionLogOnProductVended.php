<?php

declare(strict_types=1);

namespace App\Application\EventListener;

use App\Domain\Event\ProductVendedEvent;
use App\Domain\Model\TransactionLogEntry;
use App\Domain\Repository\TransactionLogRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class RecordTransactionLogOnProductVended
{
    public function __construct(
        private TransactionLogRepositoryInterface $transactionLogs,
    ) {
    }

    public function __invoke(ProductVendedEvent $event): void
    {
        $this->transactionLogs->record(new TransactionLogEntry(
            id: null,
            product: $event->product,
            price: $event->price,
            amountInserted: $event->amountInserted,
            changeReturned: $event->changeReturned,
            occurredAt: $event->occurredAt,
        ));
    }
}
