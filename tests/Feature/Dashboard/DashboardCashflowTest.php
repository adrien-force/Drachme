<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardCashflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_cashflow_reflects_user_transactions(): void
    {
        CarbonImmutable::setTestNow('2026-05-15');

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-05-05',
            'amount' => '1200.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-05-08',
            'amount' => '-300.00',
            'type' => TransactionType::Expense,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard/dashboard-index')
            ->where('isDemoData', true)
            ->has('cashflow', 12)
            ->where('kpis.monthly_cashflow', 900)
            ->where('cashflow.11.month', '2026-05')
            ->where('cashflow.11.income', 1200)
            ->where('cashflow.11.expense', 300));

        CarbonImmutable::setTestNow();
    }

    public function test_dashboard_cashflow_respects_custom_month_start_day(): void
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

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.monthly_cashflow', 500)
            ->where('cashflow.11.income', 500)
            ->where('cashflow.11.expense', 0));

        CarbonImmutable::setTestNow();
    }
}
