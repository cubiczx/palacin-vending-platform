<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Model;

use App\Domain\Exception\InvalidCoinException;
use App\Domain\Model\Coin;
use PHPUnit\Framework\TestCase;

final class CoinTest extends TestCase
{
    /** @return array<string, array{int, Coin}> */
    public static function acceptedDenominationsProvider(): array
    {
        return [
            '5 cents' => [5, Coin::FIVE_CENTS],
            '10 cents' => [10, Coin::TEN_CENTS],
            '25 cents' => [25, Coin::TWENTY_FIVE_CENTS],
            '1 euro' => [100, Coin::ONE_EURO],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('acceptedDenominationsProvider')]
    public function testFromCentsOrFailAcceptsValidDenominations(int $cents, Coin $expected): void
    {
        self::assertSame($expected, Coin::fromCentsOrFail($cents));
    }

    /** @return array<string, array{int}> */
    public static function rejectedDenominationsProvider(): array
    {
        return [
            '1 cent' => [1],
            '50 cents' => [50],
            '2 euros' => [200],
            'zero' => [0],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('rejectedDenominationsProvider')]
    public function testFromCentsOrFailRejectsInvalidDenominations(int $cents): void
    {
        $this->expectException(InvalidCoinException::class);

        Coin::fromCentsOrFail($cents);
    }

    public function testAllDescendingIsOrderedFromHighestToLowest(): void
    {
        $values = array_map(static fn (Coin $coin): int => $coin->value, Coin::allDescending());

        self::assertSame([100, 25, 10, 5], $values);
    }
}
