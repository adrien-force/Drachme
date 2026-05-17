<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_exposes_allocation_by_account_type(): void
    {
        $user = User::factory()->create();

        Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
            'initial_balance' => '3000.00',
            'current_balance' => '3000.00',
        ]);

        $invest = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'initial_balance' => '500.00',
            'current_balance' => '500.00',
        ]);

        Position::factory()->for($user)->for($invest)->create([
            'quantity' => '10',
            'average_price' => '100',
            'last_price' => '120',
        ]);

        Account::factory()->for($user)->create([
            'type' => AccountType::Credit,
            'initial_balance' => '2000.00',
            'current_balance' => '2000.00',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard/dashboard-index')
                ->where('isDemoData', false)
                ->where('kpis.total_assets', 4700)
                ->has('accountAllocation', 2)
                ->where('accountAllocation.0.type', AccountType::Checking->value)
                ->where('accountAllocation.0.value', 3000)
                ->where('accountAllocation.1.type', AccountType::Invest->value)
                ->where('accountAllocation.1.value', 1700));
    }
}
