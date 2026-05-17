<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportDuplicateAction;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Events\TransactionChanged;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CsvImport\CsvParser;
use App\Services\CsvImport\CsvRow;
use App\Support\ImportHash;
use App\Support\ImportRowError;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class ImportBatchService
{
    public function __construct(
        private readonly CsvParser $csvParser,
        private readonly ImportProviderService $providers,
        private readonly CategoryMatcher $categoryMatcher,
    ) {}

    public function createDraft(
        User $user,
        ImportProvider $provider,
        Account $account,
    ): ImportBatch {
        $this->assertOwnership($user, $provider, $account);

        return ImportBatch::query()->create([
            'user_id' => $user->id,
            'import_provider_id' => $provider->id,
            'account_id' => $account->id,
            'status' => ImportBatchStatus::Draft,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildPreview(ImportBatch $batch, UploadedFile $file): array
    {
        $provider = $batch->importProvider;
        $account = $batch->account;

        if ($provider === null || $account === null) {
            throw new InvalidArgumentException('import_batch_invalid');
        }

        $options = $provider->csv_options;
        $table = $this->csvParser->parse($file, [
            'delimiter' => (string) ($options['delimiter'] ?? ';'),
            'encoding' => (string) ($options['encoding'] ?? 'UTF-8'),
            'skip_rows' => (int) ($options['skip_rows'] ?? 0),
        ]);

        $storedPath = $this->storeFile($batch, $file);
        $previewRows = [];
        $errorCount = 0;

        foreach ($table->rows as $row) {
            $previewRows[] = $this->previewRow($row, $provider, $account, $errorCount);
        }

        $batch->update([
            'status' => ImportBatchStatus::Preview,
            'original_filename' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'preview_rows' => $previewRows,
            'error_count' => $errorCount,
            'duplicate_decisions' => null,
        ]);

        return $previewRows;
    }

    /**
     * @param  list<array{line: int, action: string}>  $decisions
     */
    public function commit(ImportBatch $batch, array $decisions): ImportBatch
    {
        if ($batch->status !== ImportBatchStatus::Preview) {
            throw new InvalidArgumentException('import_batch_not_ready');
        }

        $provider = $batch->importProvider;
        $account = $batch->account;

        if ($provider === null || $account === null) {
            throw new InvalidArgumentException('import_batch_invalid');
        }

        /** @var list<array<string, mixed>> $previewRows */
        $previewRows = $batch->preview_rows ?? [];

        $decisionMap = [];
        foreach ($decisions as $decision) {
            $line = (int) ($decision['line'] ?? 0);
            $action = ImportDuplicateAction::tryFrom((string) ($decision['action'] ?? ''));
            if ($line > 0 && $action !== null) {
                $decisionMap[$line] = $action;
            }
        }

        $imported = 0;
        $skipped = 0;
        $replaced = 0;

        DB::transaction(function () use (
            $batch,
            $account,
            $previewRows,
            $decisionMap,
            &$imported,
            &$skipped,
            &$replaced,
        ): void {
            foreach ($previewRows as $row) {
                if (($row['status'] ?? '') === 'error') {
                    continue;
                }

                $line = (int) ($row['line'] ?? 0);
                $isDuplicate = ($row['is_duplicate'] ?? false) === true;
                $action = $decisionMap[$line]
                    ?? ($isDuplicate ? ImportDuplicateAction::Skip : ImportDuplicateAction::Import);

                if ($action === ImportDuplicateAction::Skip) {
                    $skipped++;

                    continue;
                }

                if ($isDuplicate && $action === ImportDuplicateAction::Replace) {
                    $existingId = $row['existing_transaction_id'] ?? null;
                    if (is_int($existingId)) {
                        Transaction::query()
                            ->where('account_id', $account->id)
                            ->whereKey($existingId)
                            ->delete();
                    }
                    $replaced++;
                }

                if ($isDuplicate && $action === ImportDuplicateAction::Import) {
                    $row['import_hash'] = ($row['import_hash'] ?? '').':'.$batch->id.':'.$line;
                }

                $this->insertTransaction($batch, $account, $row);
                $imported++;
            }

            TransactionChanged::dispatch($account);

            $storedDecisions = [];
            foreach ($decisionMap as $line => $action) {
                $storedDecisions[] = [
                    'line' => $line,
                    'action' => $action->value,
                ];
            }

            $batch->update([
                'status' => ImportBatchStatus::Completed,
                'duplicate_decisions' => $storedDecisions,
                'imported_count' => $imported,
                'skipped_count' => $skipped,
                'replaced_count' => $replaced,
                'completed_at' => now(),
            ]);
        });

        return $batch->fresh() ?? $batch;
    }

    public function cancel(ImportBatch $batch): void
    {
        if ($batch->stored_path !== null) {
            Storage::disk('local')->delete($batch->stored_path);
        }

        $batch->update(['status' => ImportBatchStatus::Cancelled]);
    }

    /**
     * @return array<string, mixed>
     */
    private function previewRow(
        CsvRow $row,
        ImportProvider $provider,
        Account $account,
        int &$errorCount,
    ): array {
        $line = $row->lineNumber;

        try {
            $normalized = $this->providers->normalizeRow($row->cells, $provider);
            $hash = ImportHash::make(
                $account->id,
                $normalized->date,
                $normalized->amount,
                $normalized->label,
            );

            $existing = Transaction::query()
                ->where('account_id', $account->id)
                ->where('import_hash', $hash)
                ->first();

            return [
                'line' => $line,
                'date' => $normalized->date->format('Y-m-d'),
                'label' => $normalized->label,
                'amount' => $normalized->amount,
                'balance' => $normalized->balance,
                'import_hash' => $hash,
                'is_duplicate' => $existing !== null,
                'existing_transaction_id' => $existing?->id,
                'status' => 'ok',
            ];
        } catch (\Throwable $exception) {
            $errorCount++;

            return [
                'line' => $line,
                'status' => 'error',
                'error' => ImportRowError::wrapLine($line, $exception->getMessage()),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function insertTransaction(ImportBatch $batch, Account $account, array $row): void
    {
        $amount = (float) ($row['amount'] ?? 0);
        $label = (string) ($row['label'] ?? '');
        $user = User::query()->findOrFail($batch->user_id);

        Transaction::query()->create([
            'user_id' => $batch->user_id,
            'account_id' => $account->id,
            'date' => (string) ($row['date'] ?? now()->format('Y-m-d')),
            'label' => $label,
            'amount' => $amount,
            'type' => $this->transactionType($amount),
            'category_id' => $this->categoryMatcher->match($user, $label)?->id,
            'import_batch_id' => $batch->id,
            'import_hash' => (string) ($row['import_hash'] ?? ''),
        ]);
    }

    private function transactionType(float $amount): TransactionType
    {
        if ($amount < 0) {
            return TransactionType::Expense;
        }

        if ($amount > 0) {
            return TransactionType::Income;
        }

        return TransactionType::Transfer;
    }

    private function storeFile(ImportBatch $batch, UploadedFile $file): string
    {
        $directory = "imports/{$batch->user_id}";
        $filename = "{$batch->id}-".now()->timestamp.'.csv';

        $disk = Storage::disk('local');
        if (! $disk->makeDirectory($directory)) {
            throw new InvalidArgumentException('import_storage_unavailable');
        }

        $path = $file->storeAs($directory, $filename, 'local');
        if ($path === false) {
            throw new \RuntimeException('csv_unreadable');
        }

        return $path;
    }

    private function assertOwnership(
        User $user,
        ImportProvider $provider,
        Account $account,
    ): void {
        if ($provider->user_id !== $user->id || $account->user_id !== $user->id) {
            throw new InvalidArgumentException('import_forbidden');
        }
    }
}
