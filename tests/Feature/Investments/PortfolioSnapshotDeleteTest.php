<?php

declare(strict_types=1);

namespace Tests\Feature\Investments;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PortfolioSnapshotDeleteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_delete_latest_import_and_restore_previous_positions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => now()->subHour(),
            'total_market_value' => '500.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF v1',
                    'quantity' => 5,
                    'average_price' => 80,
                    'last_price' => 100,
                    'market_value' => 500,
                ],
            ],
        ]);

        $latest = PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => now(),
            'total_market_value' => '600.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF v2',
                    'quantity' => 6,
                    'average_price' => 80,
                    'last_price' => 100,
                    'market_value' => 600,
                ],
            ],
        ]);

        Position::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'isin' => 'FR0010315770',
            'label' => 'ETF v2',
            'quantity' => '6',
            'average_price' => '80',
            'last_price' => '100',
        ]);

        $this->actingAs($user)
            ->delete(route('investments.snapshots.destroy', $latest))
            ->assertRedirect(route('investments.index'));

        $this->assertDatabaseMissing('portfolio_snapshots', ['id' => $latest->id]);
        $this->assertDatabaseCount('portfolio_snapshots', 1);

        $position = Position::query()->where('account_id', $account->id)->first();
        $this->assertNotNull($position);
        $this->assertSame('ETF v1', $position->label);
        $this->assertEqualsWithDelta(5.0, (float) $position->quantity, 0.0001);
    }

    #[Test]
    public function deleting_older_import_keeps_current_positions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        $older = PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'imported_at' => now()->subHour(),
            'total_market_value' => '500.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF v1',
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
            'total_market_value' => '600.00',
            'positions_count' => 1,
            'lines' => [
                [
                    'isin' => 'FR0010315770',
                    'label' => 'ETF v2',
                    'quantity' => 6,
                    'average_price' => 80,
                    'last_price' => 100,
                    'market_value' => 600,
                ],
            ],
        ]);

        Position::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'isin' => 'FR0010315770',
            'label' => 'ETF v2',
            'quantity' => '6',
            'average_price' => '80',
            'last_price' => '100',
        ]);

        $this->actingAs($user)
            ->delete(route('investments.snapshots.destroy', $older))
            ->assertRedirect(route('investments.index'));

        $position = Position::query()->where('account_id', $account->id)->first();
        $this->assertNotNull($position);
        $this->assertSame('ETF v2', $position->label);
        $this->assertDatabaseCount('portfolio_snapshots', 1);
    }
}
