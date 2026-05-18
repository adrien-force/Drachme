<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_position_detail_with_inferred_movements(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'isin' => 'FR0010315770',
            'label' => 'ETF Test',
        ]);

        PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => '2026-01-01 10:00:00',
            'total_market_value' => '500.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF Test',
                    'quantity' => 5.0,
                    'average_price' => 80.0,
                    'last_price' => 100.0,
                    'market_value' => 500.0,
                ],
            ],
        ]);

        PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => '2026-02-01 10:00:00',
            'total_market_value' => '880.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF Test',
                    'quantity' => 8.0,
                    'average_price' => 82.0,
                    'last_price' => 110.0,
                    'market_value' => 880.0,
                ],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('positions.show', $position))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('positions/positions-show')
                ->where('position.isin', 'FR0010315770')
                ->has('inferredMovements', 2)
                ->where('inferredMovements.0.side', 'buy')
                ->where('inferredMovements.1.side', 'buy')
                ->has('portfolioValueSeries', 2)
                ->where('portfolioValueSeries.0.value', 500)
                ->where('portfolioValueSeries.1.value', 880)
                ->has('marketPriceSeries', 0));
    }

    public function test_user_cannot_view_another_users_position(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->for($owner)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($owner)->for($account)->create();

        $this->actingAs($other)
            ->get(route('positions.show', $position))
            ->assertForbidden();
    }
}
