<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use App\Services\PortfolioValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortfolioValuationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_for_user_sums_invest_positions_only(): void
    {
        $user = User::factory()->create();

        $invest = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        Position::factory()->for($user)->for($invest)->create([
            'quantity' => '2',
            'average_price' => '50',
            'last_price' => '60',
        ]);

        Position::factory()->for($user)->for($checking)->create([
            'quantity' => '99',
            'average_price' => '1000',
            'last_price' => '1000',
        ]);

        $service = app(PortfolioValuationService::class);

        $this->assertSame(120.0, $service->totalForUser($user));
        $this->assertSame(120.0, $service->totalForAccount($invest));
        $this->assertSame(0.0, $service->totalForAccount($checking));
    }

    public function test_totals_by_account_id_returns_market_value_per_invest_account(): void
    {
        $user = User::factory()->create();

        $first = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $second = Account::factory()->for($user)->create(['type' => AccountType::Invest]);

        Position::factory()->for($user)->for($first)->create([
            'quantity' => '2',
            'average_price' => '50',
            'last_price' => '60',
        ]);

        Position::factory()->for($user)->for($second)->create([
            'quantity' => '1',
            'average_price' => '100',
            'last_price' => null,
        ]);

        $service = app(PortfolioValuationService::class);

        $totals = $service->totalsByAccountId([$first, $second]);

        $this->assertSame(120.0, $totals[$first->id]);
        $this->assertSame(100.0, $totals[$second->id]);
    }

    public function test_total_for_user_ignores_archived_invest_accounts(): void
    {
        $user = User::factory()->create();

        $archived = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'is_archived' => true,
        ]);

        Position::factory()->for($user)->for($archived)->create([
            'quantity' => '10',
            'average_price' => '100',
            'last_price' => '100',
        ]);

        $service = app(PortfolioValuationService::class);

        $this->assertSame(0.0, $service->totalForUser($user));
    }
}
