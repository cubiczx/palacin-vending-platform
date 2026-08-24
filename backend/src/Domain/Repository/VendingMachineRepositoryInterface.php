<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\VendingMachine;

interface VendingMachineRepositoryInterface
{
    public function find(string $id): ?VendingMachine;

    public function save(VendingMachine $machine): void;
}
