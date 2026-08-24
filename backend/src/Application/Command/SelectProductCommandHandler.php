<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\VendingResult;
use App\Domain\Repository\VendingMachineRepositoryInterface;
use App\Domain\Service\ChangeCalculator;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Clock\ClockInterface;

final readonly class SelectProductCommandHandler
{
    public function __construct(
        private VendingMachineRepositoryInterface $machines,
        private ChangeCalculator $changeCalculator,
        private EventDispatcherInterface $eventDispatcher,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(SelectProductCommand $command): VendingResult
    {
        $machine = $this->machines->find($command->machineId)
            ?? throw new \RuntimeException("Machine \"{$command->machineId}\" not found.");

        $result = $machine->selectProduct($command->sku, $this->changeCalculator, $this->clock->now());

        $this->machines->save($machine);

        foreach ($machine->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }

        return $result;
    }
}
