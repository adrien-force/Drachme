<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SettlementPeriodMode;
use App\Models\Account;
use App\Models\Transaction;
use Carbon\CarbonImmutable;

final class CreditCardPeriodResolver
{
    public function periodStartForSettlement(
        Account $card,
        CarbonImmutable $settlementDate,
        ?CarbonImmutable $previousSettlementDate,
    ): string {
        $mode = $this->resolveMode($card);

        return match ($mode) {
            SettlementPeriodMode::CalendarMonth => $settlementDate->startOfMonth()->format('Y-m-d'),
            SettlementPeriodMode::BillingCycle => $this->billingCyclePeriodStart($card, $settlementDate),
            SettlementPeriodMode::SinceLastSettlement => $this->sinceLastSettlementStart($card, $previousSettlementDate),
        };
    }

    /**
     * First day of the open purchase window (current billing cycle, month, or since last settlement).
     */
    public function currentOpenPeriodStart(
        Account $card,
        ?CarbonImmutable $lastSettlementDate,
        ?CarbonImmutable $asOf = null,
    ): string {
        $asOf ??= CarbonImmutable::today();
        $mode = $this->resolveMode($card);

        if ($lastSettlementDate !== null) {
            $afterLastSettlement = $lastSettlementDate->addDay()->format('Y-m-d');

            return match ($mode) {
                SettlementPeriodMode::BillingCycle => max(
                    $afterLastSettlement,
                    $this->currentBillingCycleStart($card, $asOf),
                ),
                SettlementPeriodMode::CalendarMonth => max(
                    $afterLastSettlement,
                    $asOf->startOfMonth()->format('Y-m-d'),
                ),
                SettlementPeriodMode::SinceLastSettlement => $afterLastSettlement,
            };
        }

        return match ($mode) {
            SettlementPeriodMode::BillingCycle => $this->currentBillingCycleStart($card, $asOf),
            SettlementPeriodMode::CalendarMonth => $asOf->startOfMonth()->format('Y-m-d'),
            SettlementPeriodMode::SinceLastSettlement => $this->sinceLastSettlementStart($card, null),
        };
    }

    private function resolveMode(Account $card): SettlementPeriodMode
    {
        $mode = $card->settlement_period_mode;

        if ($mode instanceof SettlementPeriodMode) {
            return $mode;
        }

        if (is_string($mode) && $mode !== '') {
            return SettlementPeriodMode::tryFrom($mode) ?? SettlementPeriodMode::SinceLastSettlement;
        }

        return SettlementPeriodMode::SinceLastSettlement;
    }

    private function sinceLastSettlementStart(
        Account $card,
        ?CarbonImmutable $previousSettlementDate,
    ): string {
        if ($previousSettlementDate !== null) {
            return $previousSettlementDate->addDay()->format('Y-m-d');
        }

        $openedAt = $card->opened_at;

        if ($openedAt !== null) {
            return CarbonImmutable::parse((string) $openedAt)->format('Y-m-d');
        }

        $firstDate = Transaction::query()
            ->where('account_id', $card->id)
            ->min('date');

        if ($firstDate !== null) {
            return CarbonImmutable::parse((string) $firstDate)->format('Y-m-d');
        }

        return CarbonImmutable::today()->format('Y-m-d');
    }

    private function billingCyclePeriodStart(Account $card, CarbonImmutable $settlementDate): string
    {
        $billingDay = $card->billing_day ?? (int) $settlementDate->format('d');
        $billingDay = max(1, min(28, $billingDay));

        $previousMonth = $settlementDate->copy()->subMonth();
        $daysInPrevious = $previousMonth->daysInMonth;
        $previousCycleEnd = $previousMonth->copy()->day(min($billingDay, $daysInPrevious));

        return $previousCycleEnd->addDay()->format('Y-m-d');
    }

    private function currentBillingCycleStart(Account $card, CarbonImmutable $asOf): string
    {
        $billingDay = $card->billing_day ?? (int) $asOf->format('d');
        $billingDay = max(1, min(28, $billingDay));

        $daysInMonth = $asOf->daysInMonth;
        $cycleEnd = $asOf->copy()->day(min($billingDay, $daysInMonth));

        if ($cycleEnd->greaterThan($asOf)) {
            $previousMonth = $asOf->copy()->subMonth();
            $cycleEnd = $previousMonth->copy()->day(min($billingDay, $previousMonth->daysInMonth));
        }

        return $cycleEnd->addDay()->format('Y-m-d');
    }
}
