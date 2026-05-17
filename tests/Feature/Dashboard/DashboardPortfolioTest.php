<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPortfolioTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_includes_portfolio_evolution_after_snapshots(): void
    {
        CarbonImmutable::setTestNow('2026-05-17 14:00:00');

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'current_balance' => '1000.00',
        ]);

        PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => '2026-05-10 10:00:00',
            'total_market_value' => '5000.00',
            'positions_count' => 2,
            'lines' => [],
        ]);

        PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => '2026-05-17 14:00:00',
            'total_market_value' => '5500.00',
            'positions_count' => 2,
            'lines' => [],
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard/dashboard-index')
                ->where('isDemoData', false)
                ->has('portfolioHistory', 2)
                ->where('kpis.portfolio_value', 5500)
                ->where('portfolioHistory.1.value', 5500));

        CarbonImmutable::setTestNow();
    }
}
