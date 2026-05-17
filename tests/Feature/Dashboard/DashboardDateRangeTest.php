<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardDateRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_accepts_custom_date_range(): void
    {
        $user = User::factory()->create();

        Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
            'initial_balance' => '5000.00',
            'current_balance' => '5000.00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard', [
                'from' => '2026-01-01',
                'to' => '2026-05-15',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard/dashboard-index')
                ->where('dateRange.from', '2026-01-01')
                ->where('dateRange.to', '2026-05-15')
                ->where('dateRange.preset', 'custom')
                ->has('netWorthHistory'));
    }
}
