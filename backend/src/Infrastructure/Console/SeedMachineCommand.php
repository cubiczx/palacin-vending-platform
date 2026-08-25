<?php

declare(strict_types=1);

namespace App\Infrastructure\Console;

use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Money;
use App\Domain\Model\Product;
use App\Domain\Model\ProductSku;
use App\Domain\Model\VendingMachine;
use App\Domain\Repository\VendingMachineRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-machine',
    description: 'Seeds the default vending machine with its initial catalog and change inventory.',
)]
final class SeedMachineCommand extends Command
{
    private const string MACHINE_ID = 'machine-01';

    public function __construct(
        private readonly VendingMachineRepositoryInterface $machines,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->machines->find(self::MACHINE_ID) !== null) {
            $io->warning(sprintf('Machine "%s" already exists — skipping seed (idempotent).', self::MACHINE_ID));

            return Command::SUCCESS;
        }

        $machine = VendingMachine::create(
            id: self::MACHINE_ID,
            products: [
                new Product(ProductSku::WATER, 'Water', Money::fromCents(65), 5),
                new Product(ProductSku::JUICE, 'Juice', Money::fromCents(100), 5),
                new Product(ProductSku::SODA, 'Soda', Money::fromCents(150), 5),
            ],
            changeInventory: ChangeInventory::fromCounts([5 => 40, 10 => 40, 25 => 40, 100 => 40]),
        );

        $this->machines->save($machine);

        $io->success(sprintf('Machine "%s" seeded with 3 products and change inventory.', self::MACHINE_ID));

        return Command::SUCCESS;
    }
}
