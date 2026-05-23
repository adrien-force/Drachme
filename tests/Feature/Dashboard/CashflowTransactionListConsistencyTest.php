<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CashflowSummaryService;
use App\Services\TransactionList\TransactionListFilterApplier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard cashflow must match transaction list filters on the same date range.
 */
class CashflowTransactionListConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashflow_totals_match_list_filters_for_calendar_month(): void
    {
        $user = User::factory()->create(['month_start_day' => 1]);
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $invest = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $archived = Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
            'is_archived' => true,
        ]);

        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2024-08-10',
            'amount' => '1000.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2024-08-12',
            'amount' => '-50.00',
            'type' => TransactionType::Expense,
        ]);
        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2024-08-15',
            'amount' => '500.00',
            'type' => TransactionType::Transfer,
        ]);
        Transaction::factory()->for($user)->for($invest)->create([
            'date' => '2024-08-20',
            'amount' => '9000.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($archived)->create([
            'date' => '2024-08-20',
            'amount' => '3000.00',
            'type' => TransactionType::Income,
        ]);

        $from = CarbonImmutable::parse('2024-08-01');
        $to = CarbonImmutable::parse('2024-08-31');

        $cashflow = app(CashflowSummaryService::class)->totalsForPeriodByAmount($user, $from, $to);

        $listIncome = $this->sumWithListFilters($user, [
            'date_from' => '2024-08-01',
            'date_to' => '2024-08-31',
            'flow' => 'credit',
        ]);
        $listExpense = abs($this->sumWithListFilters($user, [
            'date_from' => '2024-08-01',
            'date_to' => '2024-08-31',
            'flow' => 'debit',
        ]));

        $this->assertSame($cashflow['income'], number_format($listIncome, 2, '.', ''));
        $this->assertSame($cashflow['expense'], number_format($listExpense, 2, '.', ''));
    }

    public function test_cashflow_series_point_matches_list_filters_for_billing_period(): void
    {
        CarbonImmutable::setTestNow('2026-05-15');

        $user = User::factory()->create(['month_start_day' => 27]);
        $account = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-05-05',
            'amount' => '400.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-04-28',
            'amount' => '100.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-04-10',
            'amount' => '999.00',
            'type' => TransactionType::Income,
        ]);

        $to = CarbonImmutable::parse('2026-05-15');
        $from = \App\Support\BillingPeriod::recentPeriodsChronological(27, 12, $to)[0]['start'];
        $series = app(CashflowSummaryService::class)->monthlySeriesForUser($user, $from, $to);
        $current = $series[array_key_last($series)];

        $listIncome = $this->sumWithListFilters($user, [
            'date_from' => $current['period_start'],
            'date_to' => $current['period_end'],
            'flow' => 'credit',
        ]);

        $this->assertSame(500.0, $current['income']);
        $this->assertSame(500.0, $listIncome);

        CarbonImmutable::setTestNow();
    }

    /**
     * Mirrors transaction list scope: active non-invest accounts, no transfers.
     *
     * @param  array<string, mixed>  $filters
     */
    private function sumWithListFilters(User $user, array $filters): float
    {
        $accountIds = Account::query()
            ->where('user_id', $user->id)
            ->active()
            ->where('type', '!=', AccountType::Invest)
            ->pluck('id');

        $query = Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('account_id', $accountIds)
            ->where('type', '!=', TransactionType::Transfer);

        app(TransactionListFilterApplier::class)->apply($query, $user, $filters);

        return (float) $query->sum('amount');
    }
}
