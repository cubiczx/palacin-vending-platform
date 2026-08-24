<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Command;

use App\Application\Command\UpdateProductPriceCommand;
use App\Application\Command\UpdateProductPriceCommandHandler;
use App\Domain\Model\Money;
use App\Domain\Model\ProductSku;
use App\Tests\Support\InMemoryVendingMachineRepository;
use App\Tests\Support\VendingMachineFixture;
use PHPUnit\Framework\TestCase;

final class UpdateProductPriceCommandHandlerTest extends TestCase
{
    public function testUpdatesProductPriceAndPersistsTheChange(): void
    {
        $repository = new InMemoryVendingMachineRepository();
        $repository->seed(VendingMachineFixture::withDefaultCatalog());
        $handler = new UpdateProductPriceCommandHandler($repository);

        $handler(new UpdateProductPriceCommand('machine-01', ProductSku::SODA, Money::fromCents(175)));

        $machine = $repository->find('machine-01');
        self::assertNotNull($machine);

        $soda = current(array_filter(
            $machine->products(),
            static fn ($p) => $p->sku() === ProductSku::SODA,
        ));
        self::assertNotFalse($soda);
        self::assertSame(175, $soda->price()->cents());
    }

    public function testThrowsWhenMachineDoesNotExist(): void
    {
        $handler = new UpdateProductPriceCommandHandler(new InMemoryVendingMachineRepository());

        $this->expectException(\RuntimeException::class);

        $handler(new UpdateProductPriceCommand('unknown-machine', ProductSku::SODA, Money::fromCents(175)));
    }
}
