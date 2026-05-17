<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Enums\AccountType;
use App\Enums\ImportBatchStatus;
use App\Enums\ImportPositionColumnField;
use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\PortfolioSnapshot;
use App\Models\User;
use App\Services\ImportBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionImportSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_import_commit_records_portfolio_snapshot(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        $provider = ImportProvider::factory()
            ->for($user)
            ->positions()
            ->create();

        $batch = ImportBatch::factory()
            ->for($user)
            ->for($provider, 'importProvider')
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Preview,
                'original_filename' => 'portfolio.csv',
                'preview_rows' => [
                    [
                        'line' => 2,
                        'label' => 'Apple',
                        'isin' => 'US0378331005',
                        'quantity' => 10,
                        'average_price' => 150.0,
                        'last_price' => 175.5,
                        'market_value' => 1755.0,
                        'is_duplicate' => false,
                        'status' => 'ok',
                    ],
                ],
            ]);

        app(ImportBatchService::class)->commit($batch, []);

        $this->assertDatabaseHas('portfolio_snapshots', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'import_batch_id' => $batch->id,
            'original_filename' => 'portfolio.csv',
            'positions_count' => 1,
        ]);

        $snapshot = PortfolioSnapshot::query()->where('import_batch_id', $batch->id)->first();
        $this->assertNotNull($snapshot);
        $this->assertSame('US0378331005', $snapshot->lines[0]['isin'] ?? null);
        $this->assertNotNull($snapshot->file_signature);
    }

    public function test_reimport_same_filename_appends_snapshot_history(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        $provider = ImportProvider::factory()
            ->for($user)
            ->positions()
            ->create();

        $mapping = [
            'columns' => [
                ['index' => 0, 'field' => ImportPositionColumnField::Isin->value],
                ['index' => 1, 'field' => ImportPositionColumnField::Quantity->value],
                ['index' => 2, 'field' => ImportPositionColumnField::AveragePrice->value],
                ['index' => 3, 'field' => ImportPositionColumnField::LastPrice->value],
            ],
        ];

        $provider->update(['column_mapping' => $mapping]);

        $service = app(ImportBatchService::class);

        foreach ([100.0, 120.0] as $lastPrice) {
            $batch = ImportBatch::factory()
                ->for($user)
                ->for($provider, 'importProvider')
                ->for($account)
                ->create([
                    'status' => ImportBatchStatus::Preview,
                    'original_filename' => 'broker-export.csv',
                    'preview_rows' => [
                        [
                            'line' => 2,
                            'label' => 'ETF',
                            'isin' => 'FR0010315770',
                            'quantity' => 5,
                            'average_price' => 80.0,
                            'last_price' => $lastPrice,
                            'market_value' => 5 * $lastPrice,
                            'is_duplicate' => $lastPrice > 100.0,
                            'status' => 'ok',
                        ],
                    ],
                ]);

            $service->commit($batch, []);
        }

        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $user->id)
            ->orderBy('imported_at')
            ->get();

        $this->assertCount(2, $snapshots);
        $this->assertSame(
            $snapshots[0]->file_signature,
            $snapshots[1]->file_signature,
        );
        $this->assertTrue((float) $snapshots[1]->total_market_value > (float) $snapshots[0]->total_market_value);
    }

    public function test_reimport_with_additional_position_creates_new_snapshot(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        $provider = ImportProvider::factory()
            ->for($user)
            ->positions()
            ->create();

        $service = app(ImportBatchService::class);

        $firstBatch = ImportBatch::factory()
            ->for($user)
            ->for($provider, 'importProvider')
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Preview,
                'original_filename' => 'portfolio.csv',
                'preview_rows' => [
                    $this->previewRow('FR0010315770', 'ETF A', 5, 80.0, 100.0, false),
                ],
            ]);

        $service->commit($firstBatch, []);

        $secondBatch = ImportBatch::factory()
            ->for($user)
            ->for($provider, 'importProvider')
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Preview,
                'original_filename' => 'portfolio.csv',
                'preview_rows' => [
                    $this->previewRow('FR0010315770', 'ETF A', 5, 80.0, 100.0, true),
                    $this->previewRow('US0378331005', 'Apple', 2, 150.0, 180.0, false),
                ],
            ]);

        $service->commit($secondBatch, []);

        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $user->id)
            ->orderBy('imported_at')
            ->get();

        $this->assertCount(2, $snapshots);
        $this->assertSame(1, $snapshots[0]->positions_count);
        $this->assertSame(2, $snapshots[1]->positions_count);
        $this->assertNotSame($snapshots[0]->id, $snapshots[1]->id);
    }

    public function test_snapshot_recorded_even_when_all_rows_skipped(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        $provider = ImportProvider::factory()
            ->for($user)
            ->positions()
            ->create();

        $batch = ImportBatch::factory()
            ->for($user)
            ->for($provider, 'importProvider')
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Preview,
                'preview_rows' => [
                    $this->previewRow('FR0010315770', 'ETF', 5, 80.0, 100.0, true),
                ],
            ]);

        app(ImportBatchService::class)->commit($batch, [
            ['line' => 2, 'action' => 'skip'],
        ]);

        $this->assertDatabaseCount('portfolio_snapshots', 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function previewRow(
        string $isin,
        string $label,
        int $quantity,
        float $averagePrice,
        float $lastPrice,
        bool $isDuplicate,
    ): array {
        return [
            'line' => 2,
            'label' => $label,
            'isin' => $isin,
            'quantity' => $quantity,
            'average_price' => $averagePrice,
            'last_price' => $lastPrice,
            'market_value' => $quantity * $lastPrice,
            'is_duplicate' => $isDuplicate,
            'status' => 'ok',
        ];
    }
}
