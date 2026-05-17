<?php

declare(strict_types=1);

namespace Tests\Feature\Investments;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvestmentsImportHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_investments_page_includes_import_history(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => now()->subHour(),
            'total_market_value' => '1000.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF',
                    'quantity' => 5,
                    'average_price' => 80,
                    'last_price' => 100,
                    'market_value' => 500,
                ],
            ],
        ]);

        PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => now(),
            'total_market_value' => '1200.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF',
                    'quantity' => 5,
                    'average_price' => 80,
                    'last_price' => 120,
                    'market_value' => 600,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('investments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('investments/investments-index')
                ->has('accounts', 1)
                ->has('accounts.0.import_history', 2)
                ->where('accounts.0.import_history.0.total_market_value', 1200)
                ->where('accounts.0.import_history.0.change_pct', 20));
    }
}
