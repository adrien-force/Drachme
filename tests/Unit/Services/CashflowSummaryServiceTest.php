<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CashflowSummaryService;
use App\Support\BillingPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashflowSummaryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_series_returns_twelve_months_with_zeros_when_empty(): void
    {
        $user = User::factory()->create();

        $to = CarbonImmutable::today();
        $from = BillingPeriod::recentPeriodsChronological(1, 12, $to)[0]['start'];
        $series = app(CashflowSummaryService::class)->monthlySeriesForUser($user, $from, $to);

        $this->assertCount(12, $series);
        $this->assertArrayHasKey('period_start', $series[0]);
        $this->assertSame(0.0, $series[0]['income']);
        $this->assertSame(0.0, $series[0]['expense']);
        $this->assertNotSame('', $series[0]['label']);
    }

    public function test_totals_for_period_by_amount_uses_amount_signs(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-08-10',
            'amount' => '1000.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-08-12',
            'amount' => '-50.00',
            'type' => TransactionType::Expense,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-08-15',
            'amount' => '500.00',
            'type' => TransactionType::Transfer,
        ]);

        $totals = app(CashflowSummaryService::class)->totalsForPeriodByAmount(
            $user,
            CarbonImmutable::parse('2024-08-01'),
            CarbonImmutable::parse('2024-08-31'),
        );

        $this->assertSame('1000.00', $totals['income']);
        $this->assertSame('50.00', $totals['expense']);
        $this->assertSame('950.00', $totals['net']);
    }

    public function test_invest_account_transactions_are_excluded_by_default(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
        ]);
        $invest = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2024-08-10',
            'amount' => '200.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($invest)->create([
            'date' => '2024-08-10',
            'amount' => '9000.00',
            'type' => TransactionType::Income,
        ]);

        $totals = app(CashflowSummaryService::class)->totalsForPeriodByAmount(
            $user,
            CarbonImmutable::parse('2024-08-01'),
            CarbonImmutable::parse('2024-08-31'),
        );

        $this->assertSame('200.00', $totals['income']);
    }

    public function test_monthly_series_uses_user_month_start_day(): void
    {
        CarbonImmutable::setTestNow('2026-05-15');

        $user = User::factory()->create(['month_start_day' => 27]);
        $this->actingAs($user);

        $account = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-05-05',
            'amount' => '500.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-04-28',
            'amount' => '200.00',
            'type' => TransactionType::Income,
        ]);

        $to = CarbonImmutable::today();
        $from = BillingPeriod::recentPeriodsChronological(27, 12, $to)[0]['start'];
        $series = app(CashflowSummaryService::class)->monthlySeriesForUser($user, $from, $to);
        $current = $series[array_key_last($series)];

        $this->assertSame(700.0, $current['income']);
        $this->assertSame(0.0, $current['expense']);

        CarbonImmutable::setTestNow();
    }

    public function test_archived_account_transactions_are_excluded(): void
    {
        $user = User::factory()->create();
        $active = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $archived = Account::factory()->for($user)->create([
            'is_archived' => true,
        ]);

        Transaction::factory()->for($user)->for($active)->create([
            'date' => '2024-08-10',
            'amount' => '100.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($archived)->create([
            'date' => '2024-08-10',
            'amount' => '5000.00',
            'type' => TransactionType::Income,
        ]);

        $totals = app(CashflowSummaryService::class)->totalsForPeriodByAmount(
            $user,
            CarbonImmutable::parse('2024-08-01'),
            CarbonImmutable::parse('2024-08-31'),
        );

        $this->assertSame('100.00', $totals['income']);
    }
}
