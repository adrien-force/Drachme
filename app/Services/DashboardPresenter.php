<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\NetWorthSnapshot;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;

class DashboardPresenter
{
    public function __construct(
        private readonly CashflowSummaryService $cashflow,
        private readonly NetWorthSnapshotService $netWorth,
        private readonly PortfolioValuationService $portfolioValuation,
        private readonly PortfolioSnapshotService $portfolioSnapshots,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $cashflowSeries = $this->cashflow->monthlySeriesForUser($user);
        $monthlyCashflow = $this->cashflow->currentPeriodNetForUser($user);
        $totals = $this->netWorth->totalsForUser($user);
        $netWorthValue = (float) $totals['net_worth'];
        $portfolioHistory = $this->portfolioSnapshots->evolutionSeriesForUser($user);
        $portfolioValue = $this->resolvePortfolioValue($user, $portfolioHistory);

        $netWorthHistory = $this->netWorthHistory($user, $netWorthValue);

        return [
            'kpis' => [
                'net_worth' => $netWorthValue,
                'net_worth_change_pct' => $this->netWorthChangePct($netWorthHistory, $netWorthValue),
                'monthly_cashflow' => $monthlyCashflow,
                'portfolio_value' => $portfolioValue,
                'portfolio_change_pct' => $this->portfolioChangePct($portfolioHistory, $portfolioValue),
            ],
            'netWorthHistory' => $netWorthHistory,
            'portfolioHistory' => $portfolioHistory,
            'cashflow' => $cashflowSeries,
            'isDemoData' => ! $this->hasRealData($user, $netWorthHistory, $portfolioHistory),
        ];
    }

    /**
     * @param  list<array{value: float}>  $portfolioHistory
     */
    private function resolvePortfolioValue(User $user, array $portfolioHistory): float
    {
        $liveValue = $this->portfolioValuation->totalForUser($user);

        if ($liveValue > 0) {
            return $liveValue;
        }

        if ($portfolioHistory === []) {
            return 0.0;
        }

        return $portfolioHistory[count($portfolioHistory) - 1]['value'];
    }

    /**
     * @param  list<array{month: string, label: string, value: float}>  $history
     */
    private function netWorthChangePct(array $history, float $currentNetWorth): float
    {
        if (count($history) < 2) {
            return 0.0;
        }

        $previous = $history[count($history) - 2]['value'];

        if ($previous <= 0) {
            return 0.0;
        }

        return round((($currentNetWorth - $previous) / $previous) * 100, 1);
    }

    /**
     * @param  list<array{value: float}>  $history
     */
    private function portfolioChangePct(array $history, float $currentPortfolio): float
    {
        if (count($history) < 2) {
            return 0.0;
        }

        $previous = $history[count($history) - 2]['value'];

        if ($previous <= 0) {
            return 0.0;
        }

        return round((($currentPortfolio - $previous) / $previous) * 100, 1);
    }

    /**
     * @return list<array{month: string, label: string, value: float}>
     */
    private function netWorthHistory(User $user, float $currentNetWorth): array
    {
        $points = $this->netWorth->historyPointsForUser($user);

        if ($points === []) {
            if (! $this->userHasFinancialActivity($user)) {
                /** @var list<array{month: string, label: string, value: float}> */
                return config('dummy-dashboard.net_worth_history');
            }

            $today = CarbonImmutable::today();
            $locale = app()->getLocale();

            return [[
                'month' => $today->format('Y-m'),
                'label' => $today->format('M Y'),
                'value' => $currentNetWorth,
            ]];
        }

        $lastIndex = count($points) - 1;
        $points[$lastIndex]['value'] = $currentNetWorth;

        /** @var list<array{month: string, label: string, value: float}> */
        return $points;
    }

    /**
     * @param  list<array{month: string, label: string, value: float}>  $netWorthHistory
     * @param  list<array<string, mixed>>  $portfolioHistory
     */
    private function hasRealData(User $user, array $netWorthHistory, array $portfolioHistory): bool
    {
        if ($portfolioHistory !== []) {
            return true;
        }

        if (NetWorthSnapshot::query()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($this->userHasFinancialActivity($user)) {
            return true;
        }

        return false;
    }

    private function userHasFinancialActivity(User $user): bool
    {
        if (Transaction::query()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if (Position::query()->where('user_id', $user->id)->exists()) {
            return true;
        }

        return Account::query()
            ->where('user_id', $user->id)
            ->where('is_archived', false)
            ->exists();
    }
}
