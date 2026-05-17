<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class DashboardPresenter
{
    public function __construct(
        private readonly CashflowSummaryService $cashflow,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(User $user): array
    {
        $cashflowSeries = $this->cashflow->monthlySeriesForUser($user);
        $monthlyCashflow = $this->cashflow->currentPeriodNetForUser($user);

        return [
            'kpis' => [
                'net_worth' => config('dummy-dashboard.kpis.net_worth'),
                'net_worth_change_pct' => config('dummy-dashboard.kpis.net_worth_change_pct'),
                'monthly_cashflow' => $monthlyCashflow,
            ],
            'netWorthHistory' => config('dummy-dashboard.net_worth_history'),
            'cashflow' => $cashflowSeries,
            'isDemoData' => true,
        ];
    }
}
