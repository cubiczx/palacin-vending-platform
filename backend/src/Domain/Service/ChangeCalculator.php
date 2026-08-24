<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Exception\ExactChangeUnavailableException;
use App\Domain\Model\ChangeInventory;
use App\Domain\Model\Coin;
use App\Domain\Model\Money;

/**
 * Stateless domain service that decides which physical coins the machine
 * should return for a given amount of change.
 *
 * Uses a greedy algorithm (always take the largest denomination that
 * fits), which is optimal for this canonical denomination set
 * (5, 10, 25, 100 cents) under unlimited supply. Because the machine's
 * supply is limited, the greedy result is validated against the
 * available inventory; if it can't be satisfied exactly, an
 * ExactChangeUnavailableException is thrown rather than shortchanging
 * the customer.
 *
 * Known trade-off: a handful of edge cases exist where greedy fails
 * despite a valid combination existing using more low-value coins
 * (e.g. plenty of 0.05/0.10 but zero 0.25 in stock for 0.30 due).
 * An exhaustive/backtracking search would close this gap; given the
 * small, bounded denomination set this is an intentional, documented
 * simplification (see README "Trade-offs").
 */
final class ChangeCalculator
{
    /**
     * @return array<int, int> Coin value in cents => quantity to return
     *
     * @throws ExactChangeUnavailableException
     */
    public function calculate(Money $amount, ChangeInventory $available): array
    {
        $remaining = $amount->cents();
        $result = [];

        foreach (Coin::allDescending() as $coin) {
            if ($remaining <= 0) {
                break;
            }

            $maxUsable = intdiv($remaining, $coin->value);
            $usable = min($maxUsable, $available->quantityOf($coin));

            if ($usable > 0) {
                $result[$coin->value] = $usable;
                $remaining -= $usable * $coin->value;
            }
        }

        if ($remaining > 0) {
            throw ExactChangeUnavailableException::forAmount($amount);
        }

        return $result;
    }
}
