<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\ImportBatchStatus;
use App\Enums\ImportDuplicateAction;
use App\Enums\ImportProviderType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Position;
use App\Events\TransactionChanged;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CsvImport\CsvParser;
use App\Services\CsvImport\CsvRow;
use App\Support\ImportHash;
use App\Support\ImportRowError;
use App\Support\Isin;
use App\Support\Utf8Normalizer;
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
        private readonly PositionService $positions,
        private readonly PortfolioSnapshotService $portfolioSnapshots,
        private readonly NetWorthSnapshotService $netWorthSnapshots,
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
            $previewRows[] = $provider->isPositionsImport()
                ? $this->previewPositionRow($row, $provider, $account, $errorCount)
                : $this->previewRow($row, $provider, $account, $errorCount);
        }

        $batch->update([
            'status' => ImportBatchStatus::Preview,
            'original_filename' => Utf8Normalizer::ensureValid(
                (string) $file->getClientOriginalName(),
            ),
            'stored_path' => $storedPath,
            'preview_rows' => Utf8Normalizer::sanitizeArray($previewRows),
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

        $isPositionsImport = $provider->isPositionsImport();

        DB::transaction(function () use (
            $batch,
            $account,
            $previewRows,
            $decisionMap,
            $isPositionsImport,
            &$imported,
            &$skipped,
            &$replaced,
        ): void {
            if (! $isPositionsImport) {
                Transaction::query()
                    ->where('account_id', $account->id)
                    ->where('import_batch_id', $batch->id)
                    ->delete();
            }

            /** @var array<string, true> $hashesCommittedInBatch */
            $hashesCommittedInBatch = [];

            foreach ($previewRows as $row) {
                if (($row['status'] ?? '') === 'error') {
                    continue;
                }

                $line = (int) ($row['line'] ?? 0);
                $isDuplicate = ($row['is_duplicate'] ?? false) === true;
                $defaultDuplicateAction = $isPositionsImport
                    ? ImportDuplicateAction::Import
                    : ImportDuplicateAction::Skip;

                $action = $decisionMap[$line]
                    ?? ($isDuplicate ? $defaultDuplicateAction : ImportDuplicateAction::Import);

                if ($action === ImportDuplicateAction::Skip) {
                    $skipped++;

                    continue;
                }

                if ($isPositionsImport) {
                    if ($isDuplicate) {
                        $replaced++;
                    }

                    $this->upsertPosition($batch, $account, $row);
                    $imported++;

                    continue;
                }

                if ($isDuplicate && $action === ImportDuplicateAction::Replace) {
                    $replaced++;
                }

                $resolvedHash = $this->resolveTransactionImportHash(
                    $account,
                    $batch,
                    $row,
                    $line,
                    $isDuplicate,
                    $action,
                    $hashesCommittedInBatch,
                );

                if ($resolvedHash === null) {
                    $skipped++;

                    continue;
                }

                $row['import_hash'] = $resolvedHash;

                $this->insertTransaction($batch, $account, $row);
                $imported++;
            }

            if ($isPositionsImport) {
                $this->syncPositionsToImportFile($account, (int) $batch->user_id, $previewRows);
            }

            if (! $isPositionsImport) {
                TransactionChanged::dispatch($account);
            }

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

        $batch = $batch->fresh() ?? $batch;

        if ($isPositionsImport && $this->hasValidPreviewRows($previewRows)) {
            $batch->load('importProvider');
            $this->portfolioSnapshots->recordFromImport($batch, $account);
            $owner = User::query()->find($batch->user_id);

            if ($owner !== null) {
                $this->netWorthSnapshots->recordForUser($owner);
            }
        }

        return $batch;
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
    /**
     * @return array<string, mixed>
     */
    private function previewPositionRow(
        CsvRow $row,
        ImportProvider $provider,
        Account $account,
        int &$errorCount,
    ): array {
        $line = $row->lineNumber;

        try {
            $normalized = $this->providers->normalizePositionRow($row->cells, $provider);
            $isin = $normalized['isin'];

            $existing = Position::query()
                ->where('account_id', $account->id)
                ->where('isin', $isin)
                ->first();

            $quantity = (float) $normalized['quantity'];
            $unitPrice = $normalized['last_price'] !== null
                ? (float) $normalized['last_price']
                : (float) $normalized['average_price'];

            return [
                'line' => $line,
                'label' => $normalized['label'],
                'isin' => $isin,
                'market_symbol' => $normalized['market_symbol'] ?? null,
                'quantity' => $quantity,
                'average_price' => (float) $normalized['average_price'],
                'last_price' => $normalized['last_price'] !== null
                    ? (float) $normalized['last_price']
                    : null,
                'market_value' => $quantity * $unitPrice,
                'is_duplicate' => $existing !== null,
                'existing_position_id' => $existing?->id,
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
    /**
     * @param  list<array<string, mixed>>  $previewRows
     */
    private function syncPositionsToImportFile(Account $account, int $userId, array $previewRows): void
    {
        $isinsInFile = [];

        foreach ($previewRows as $row) {
            if (($row['status'] ?? '') !== 'ok') {
                continue;
            }

            $isinRaw = trim((string) ($row['isin'] ?? ''));

            if ($isinRaw === '' || ! Isin::isValid($isinRaw)) {
                continue;
            }

            $isinsInFile[] = Isin::normalize($isinRaw);
        }

        if ($isinsInFile === []) {
            return;
        }

        Position::query()
            ->where('account_id', $account->id)
            ->where('user_id', $userId)
            ->whereNotIn('isin', array_values(array_unique($isinsInFile)))
            ->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $previewRows
     */
    private function hasValidPreviewRows(array $previewRows): bool
    {
        foreach ($previewRows as $row) {
            if (($row['status'] ?? '') === 'ok') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function upsertPosition(ImportBatch $batch, Account $account, array $row): void
    {
        $user = User::query()->findOrFail($batch->user_id);

        $this->positions->upsertFromImport($user, $account, [
            'isin' => (string) ($row['isin'] ?? ''),
            'label' => (string) ($row['label'] ?? ''),
            'market_symbol' => array_key_exists('market_symbol', $row) && is_string($row['market_symbol'])
                ? $row['market_symbol']
                : null,
            'quantity' => (string) ($row['quantity'] ?? '0'),
            'average_price' => (string) ($row['average_price'] ?? '0'),
            'last_price' => array_key_exists('last_price', $row) && $row['last_price'] !== null
                ? (string) $row['last_price']
                : null,
        ]);
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
     * Resolves a unique import hash, or null when the row should be skipped (already in DB).
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, true>  $hashesCommittedInBatch
     */
    private function resolveTransactionImportHash(
        Account $account,
        ImportBatch $batch,
        array $row,
        int $line,
        bool $isDuplicate,
        ImportDuplicateAction $action,
        array &$hashesCommittedInBatch,
    ): ?string {
        $hash = (string) ($row['import_hash'] ?? '');

        if ($hash === '') {
            return '';
        }

        $existsInDb = Transaction::query()
            ->where('account_id', $account->id)
            ->where('import_hash', $hash)
            ->exists();
        $existsInBatch = isset($hashesCommittedInBatch[$hash]);

        if ($action === ImportDuplicateAction::Replace && ($existsInDb || $existsInBatch)) {
            Transaction::query()
                ->where('account_id', $account->id)
                ->where('import_hash', $hash)
                ->delete();

            unset($hashesCommittedInBatch[$hash]);
            $existsInDb = false;
            $existsInBatch = false;
        }

        if ($existsInBatch || ($existsInDb && $isDuplicate)) {
            $hash = ImportHash::disambiguate($hash, $batch->id, $line);
        } elseif ($existsInDb) {
            return null;
        }

        $hashesCommittedInBatch[$hash] = true;

        return $hash;
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

        if ($provider->import_type === ImportProviderType::Positions && $account->type !== AccountType::Invest) {
            throw new InvalidArgumentException('import_account_must_be_invest');
        }
    }
}
