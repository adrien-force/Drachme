<?php

declare(strict_types=1);

namespace Tests\Feature\Import;

use App\Enums\ImportBatchStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ImportProviderService;
use App\Support\ImportHash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImportWizardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_can_complete_csv_import_wizard(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '1000.00',
            'current_balance' => '1000.00',
        ]);

        $provider = ImportProvider::factory()->for($user)->create([
            'default_account_id' => $account->id,
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => 'date'],
                    ['index' => 1, 'field' => 'label'],
                    ['index' => 2, 'field' => 'amount_signed'],
                ],
            ],
            'csv_options' => array_merge(
                app(ImportProviderService::class)->defaultCsvOptions(),
                ['date_format' => 'd/m/Y', 'skip_rows' => 0],
            ),
        ]);

        $this->actingAs($user)
            ->post(route('import.store'), [
                'import_provider_id' => $provider->id,
                'account_id' => $account->id,
            ])
            ->assertRedirect();

        $batch = ImportBatch::query()->first();
        $this->assertNotNull($batch);
        $this->assertSame(ImportBatchStatus::Draft, $batch->status);

        $csv = "01/02/2024;Supermarket;-42,50\n";
        $file = UploadedFile::fake()->createWithContent('releve.csv', $csv);

        $this->actingAs($user)
            ->post(route('import.parse', $batch), ['file' => $file])
            ->assertRedirect(route('import.show', $batch));

        $batch->refresh();
        $this->assertSame(ImportBatchStatus::Preview, $batch->status);
        $this->assertCount(1, $batch->preview_rows);

        $this->actingAs($user)
            ->post(route('import.commit', $batch), ['decisions' => []])
            ->assertRedirect(route('import.show', $batch));

        $batch->refresh();
        $account->refresh();

        $this->assertSame(ImportBatchStatus::Completed, $batch->status);
        $this->assertSame(1, $batch->imported_count);
        $this->assertDatabaseCount('transactions', 1);

        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('-42.50', $transaction->amount);
        $this->assertSame('957.50', $account->current_balance);
    }

    #[Test]
    public function duplicate_rows_can_be_skipped_by_default(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '0.00',
            'current_balance' => '0.00',
        ]);

        $provider = ImportProvider::factory()->for($user)->create([
            'csv_options' => array_merge(
                app(ImportProviderService::class)->defaultCsvOptions(),
                ['date_format' => 'd/m/Y', 'skip_rows' => 0],
            ),
        ]);

        $existingHash = ImportHash::make(
            $account->id,
            now()->setDate(2024, 2, 1),
            -10.0,
            'Duplicate',
        );

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-02-01',
            'label' => 'Duplicate',
            'amount' => '-10.00',
            'import_hash' => $existingHash,
        ]);

        $batch = ImportBatch::factory()
            ->for($user)
            ->for($provider)
            ->for($account)
            ->create(['status' => ImportBatchStatus::Draft]);

        $csv = "01/02/2024;Duplicate;-10,00\n";
        $file = UploadedFile::fake()->createWithContent('dup.csv', $csv);

        $this->actingAs($user)
            ->post(route('import.parse', $batch), ['file' => $file]);

        $batch->refresh();
        $this->assertTrue($batch->preview_rows[0]['is_duplicate']);

        $this->actingAs($user)
            ->post(route('import.commit', $batch), ['decisions' => []]);

        $batch->refresh();
        $this->assertSame(0, $batch->imported_count);
        $this->assertSame(1, $batch->skipped_count);
        $this->assertDatabaseCount('transactions', 1);
    }

    #[Test]
    public function commit_skips_rows_already_in_database_when_preview_missed_duplicate(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'csv_options' => array_merge(
                app(ImportProviderService::class)->defaultCsvOptions(),
                ['date_format' => 'd/m/Y', 'skip_rows' => 0],
            ),
        ]);

        $hash = ImportHash::make(
            $account->id,
            now()->setDate(2024, 6, 4),
            -7.99,
            'PayPal',
        );

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-06-04',
            'label' => 'PayPal',
            'amount' => '-7.99',
            'import_hash' => $hash,
        ]);

        $batch = ImportBatch::factory()
            ->for($user)
            ->for($provider)
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Preview,
                'preview_rows' => [[
                    'line' => 2,
                    'date' => '2024-06-04',
                    'label' => 'PayPal',
                    'amount' => -7.99,
                    'import_hash' => $hash,
                    'is_duplicate' => false,
                    'status' => 'ok',
                ]],
            ]);

        $this->actingAs($user)
            ->post(route('import.commit', $batch), ['decisions' => []])
            ->assertRedirect();

        $batch->refresh();
        $this->assertSame(0, $batch->imported_count);
        $this->assertSame(1, $batch->skipped_count);
        $this->assertDatabaseCount('transactions', 1);
    }

    #[Test]
    public function commit_imports_identical_csv_lines_with_distinct_hashes(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'csv_options' => array_merge(
                app(ImportProviderService::class)->defaultCsvOptions(),
                ['date_format' => 'd/m/Y', 'skip_rows' => 0],
            ),
        ]);

        $hash = ImportHash::make(
            $account->id,
            now()->setDate(2024, 6, 4),
            -7.99,
            'PayPal',
        );

        $batch = ImportBatch::factory()
            ->for($user)
            ->for($provider)
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Preview,
                'preview_rows' => [
                    [
                        'line' => 2,
                        'date' => '2024-06-04',
                        'label' => 'PayPal',
                        'amount' => -7.99,
                        'import_hash' => $hash,
                        'is_duplicate' => false,
                        'status' => 'ok',
                    ],
                    [
                        'line' => 3,
                        'date' => '2024-06-04',
                        'label' => 'PayPal',
                        'amount' => -7.99,
                        'import_hash' => $hash,
                        'is_duplicate' => false,
                        'status' => 'ok',
                    ],
                ],
            ]);

        $this->actingAs($user)
            ->post(route('import.commit', $batch), ['decisions' => []])
            ->assertRedirect();

        $batch->refresh();
        $this->assertSame(2, $batch->imported_count);
        $this->assertDatabaseCount('transactions', 2);
        $this->assertSame(2, Transaction::query()->where('account_id', $account->id)->count());
    }

    #[Test]
    public function user_can_start_import_on_account_not_linked_to_provider(): void
    {
        $user = User::factory()->create();
        $linked = Account::factory()->for($user)->create(['name' => 'Linked']);
        $other = Account::factory()->for($user)->create(['name' => 'Other']);

        $provider = ImportProvider::factory()->for($user)->create([
            'default_account_id' => $linked->id,
        ]);
        $provider->accounts()->sync([$linked->id]);

        $this->actingAs($user)
            ->post(route('import.store'), [
                'import_provider_id' => $provider->id,
                'account_id' => $other->id,
            ])
            ->assertRedirect();

        $batch = ImportBatch::query()->first();
        $this->assertNotNull($batch);
        $this->assertSame($other->id, $batch->account_id);
    }

    #[Test]
    public function commit_removes_orphan_transactions_from_same_preview_batch(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'csv_options' => array_merge(
                app(ImportProviderService::class)->defaultCsvOptions(),
                ['date_format' => 'd/m/Y', 'skip_rows' => 0],
            ),
        ]);

        $hash = ImportHash::make(
            $account->id,
            now()->setDate(2024, 6, 4),
            -7.99,
            'PayPal',
        );

        $batch = ImportBatch::factory()
            ->for($user)
            ->for($provider)
            ->for($account)
            ->create([
                'status' => ImportBatchStatus::Preview,
                'preview_rows' => [[
                    'line' => 2,
                    'date' => '2024-06-04',
                    'label' => 'PayPal',
                    'amount' => -7.99,
                    'import_hash' => $hash,
                    'is_duplicate' => false,
                    'status' => 'ok',
                ]],
            ]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-06-04',
            'label' => 'PayPal',
            'amount' => '-7.99',
            'import_hash' => $hash,
            'import_batch_id' => $batch->id,
        ]);

        $this->actingAs($user)
            ->post(route('import.commit', $batch), ['decisions' => []])
            ->assertRedirect();

        $batch->refresh();
        $this->assertSame(1, $batch->imported_count);
        $this->assertDatabaseCount('transactions', 1);
        $this->assertSame($batch->id, Transaction::query()->value('import_batch_id'));
    }
}
