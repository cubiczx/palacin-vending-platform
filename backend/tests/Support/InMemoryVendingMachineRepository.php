<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Model\VendingMachine;
use App\Domain\Repository\VendingMachineRepositoryInterface;

/**
 * Test double for VendingMachineRepositoryInterface. Stores the aggregate
 * by reference in memory so Application-layer tests can assert on
 * post-handler state without touching MongoDB.
 */
final class InMemoryVendingMachineRepository implements VendingMachineRepositoryInterface
{
    /** @var array<string, VendingMachine> */
    private array $machines = [];

    public function seed(VendingMachine $machine): void
    {
        $this->machines[$machine->id()] = $machine;
    }

    public function find(string $id): ?VendingMachine
    {
        return $this->machines[$id] ?? null;
    }

    public function save(VendingMachine $machine): void
    {
        $this->machines[$machine->id()] = $machine;
    }
}
