<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Models\User;
use App\Services\PositionSnapshotMovementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionSnapshotMovementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_infers_buy_on_first_snapshot_and_sell_on_removal(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'isin' => 'FR0010315770',
            'quantity' => '3',
        ]);

        $this->createSnapshot($user->id, $account->id, '2026-01-01 10:00:00', [
            $this->line('FR0010315770', 5, 80.0, 100.0),
        ]);

        $this->createSnapshot($user->id, $account->id, '2026-02-01 10:00:00', [
            $this->line('FR0010315770', 8, 82.0, 110.0),
        ]);

        $this->createSnapshot($user->id, $account->id, '2026-03-01 10:00:00', []);

        $service = app(PositionSnapshotMovementService::class);
        $movements = $service->inferredMovementsForPosition($position);

        $this->assertCount(3, $movements);
        $this->assertSame('buy', $movements[0]['side']);
        $this->assertSame(5.0, $movements[0]['quantity']);
        $this->assertSame('buy', $movements[1]['side']);
        $this->assertSame(3.0, $movements[1]['quantity']);
        $this->assertSame('sell', $movements[2]['side']);
        $this->assertSame(8.0, $movements[2]['quantity']);
    }

    public function test_infers_partial_sell_between_snapshots(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'isin' => 'IE00B4L5Y983',
        ]);

        $this->createSnapshot($user->id, $account->id, '2026-01-01 10:00:00', [
            $this->line('IE00B4L5Y983', 10, 50.0, 55.0),
        ]);

        $this->createSnapshot($user->id, $account->id, '2026-02-01 10:00:00', [
            $this->line('IE00B4L5Y983', 6, 50.0, 60.0),
        ]);

        $movements = app(PositionSnapshotMovementService::class)
            ->inferredMovementsForPosition($position);

        $this->assertCount(2, $movements);
        $this->assertSame('buy', $movements[0]['side']);
        $this->assertSame('sell', $movements[1]['side']);
        $this->assertSame(4.0, $movements[1]['quantity']);
        $this->assertSame(60.0, $movements[1]['unit_price']);
    }

    public function test_portfolio_value_series_uses_imported_market_value(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'isin' => 'FR0010315770',
        ]);

        $this->createSnapshot($user->id, $account->id, '2026-01-15 12:00:00', [
            $this->line('FR0010315770', 5, 80.0, 100.0),
        ]);

        $series = app(PositionSnapshotMovementService::class)
            ->snapshotPortfolioValueSeriesForPosition($position);

        $this->assertCount(1, $series);
        $this->assertSame('2026-01-15', $series[0]['date']);
        $this->assertSame(500.0, $series[0]['value']);
    }

    public function test_portfolio_value_series_falls_back_to_quantity_times_price(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'isin' => 'FR0010315770',
        ]);

        $this->createSnapshot($user->id, $account->id, '2026-02-01 12:00:00', [
            [
                'isin' => 'FR0010315770',
                'label' => 'ETF',
                'quantity' => 2.0,
                'average_price' => 50.0,
                'last_price' => 60.0,
            ],
        ]);

        $series = app(PositionSnapshotMovementService::class)
            ->snapshotPortfolioValueSeriesForPosition($position);

        $this->assertCount(1, $series);
        $this->assertSame(120.0, $series[0]['value']);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function createSnapshot(int $userId, int $accountId, string $importedAt, array $lines): void
    {
        $total = array_sum(array_map(
            static fn (array $line): float => (float) ($line['market_value'] ?? 0),
            $lines,
        ));

        PortfolioSnapshot::query()->create([
            'user_id' => $userId,
            'account_id' => $accountId,
            'imported_at' => $importedAt,
            'total_market_value' => number_format($total, 2, '.', ''),
            'positions_count' => count($lines),
            'lines' => $lines,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function line(string $isin, float $quantity, float $averagePrice, ?float $lastPrice): array
    {
        $price = $lastPrice ?? $averagePrice;

        return [
            'isin' => $isin,
            'label' => 'Test',
            'quantity' => $quantity,
            'average_price' => $averagePrice,
            'last_price' => $lastPrice,
            'market_value' => $quantity * $price,
        ];
    }
}
