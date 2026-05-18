<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\ImportProvider;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class PortfolioSnapshotService
{
    public function __construct(
        private readonly PositionService $positions,
        private readonly NetWorthSnapshotService $netWorthSnapshots,
    ) {}

    public static function fileSignature(ImportProvider $provider, ?string $originalFilename): ?string
    {
        if ($originalFilename === null || trim($originalFilename) === '') {
            return null;
        }

        $normalized = mb_strtolower(trim($originalFilename));

        return hash('sha256', 'provider:'.$provider->id.'|file:'.$normalized);
    }

    public function recordFromImport(ImportBatch $batch, Account $account): PortfolioSnapshot
    {
        $user = $batch->user;
        $provider = $batch->importProvider;

        if ($user === null) {
            throw new \InvalidArgumentException('import_batch_invalid');
        }

        $positions = Position::query()
            ->where('account_id', $account->id)
            ->where('user_id', $user->id)
            ->orderBy('isin')
            ->get();

        $lines = $positions->map(function (Position $position): array {
            $marketValue = $this->positions->marketValue($position);

            return [
                'isin' => $position->isin,
                'label' => $position->label,
                'quantity' => (float) $position->quantity,
                'average_price' => (float) $position->average_price,
                'last_price' => $position->last_price !== null
                    ? (float) $position->last_price
                    : null,
                'market_value' => $marketValue,
            ];
        })->values()->all();

        $total = array_sum(array_column($lines, 'market_value'));

        return PortfolioSnapshot::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'import_batch_id' => $batch->id,
            'imported_at' => now(),
            'file_signature' => $provider !== null
                ? self::fileSignature($provider, $batch->original_filename)
                : null,
            'original_filename' => $batch->original_filename,
            'total_market_value' => number_format($total, 2, '.', ''),
            'positions_count' => count($lines),
            'lines' => $lines,
        ]);
    }

    /**
     * User-level portfolio total over time (cumulative knowledge per invest account).
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     value: float,
     *     imported_at: string,
     *     account_id: int,
     *     account_name: string,
     *     original_filename: string|null,
     * }>
     */
    public function evolutionSeriesForUser(User $user, int $limit = 24): array
    {
        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $user->id)
            ->with('account:id,name')
            ->orderByDesc('imported_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        if ($snapshots->isEmpty()) {
            return [];
        }

        /** @var array<int, float> $latestByAccount */
        $latestByAccount = [];
        $points = [];

        foreach ($snapshots as $snapshot) {
            $latestByAccount[$snapshot->account_id] = (float) $snapshot->total_market_value;
            $importedAt = $this->resolveImportedAt($snapshot->imported_at);

            $points[] = [
                'key' => $importedAt->format('Y-m-d-His').'-'.$snapshot->id,
                'label' => $importedAt->format('d/m/Y H:i'),
                'value' => array_sum($latestByAccount),
                'imported_at' => $importedAt->toIso8601String(),
                'account_id' => $snapshot->account_id,
                'account_name' => $snapshot->account !== null ? $snapshot->account->name : '',
                'original_filename' => $snapshot->original_filename,
            ];
        }

        return $points;
    }

    /**
     * Per-account import history with line-level deltas vs previous snapshot.
     *
     * @return list<array{
     *     id: int,
     *     imported_at: string,
     *     label: string,
     *     total_market_value: float,
     *     positions_count: int,
     *     change_amount: float|null,
     *     change_pct: float|null,
     *     original_filename: string|null,
     *     lines: list<array{
     *         isin: string,
     *         label: string,
     *         quantity: float,
     *         average_price: float,
     *         last_price: float|null,
     *         market_value: float,
     *         quantity_delta: float|null,
     *         value_delta: float|null,
     *     }>,
     * }>
     */
    public function detailedHistoryForAccount(User $user, int $accountId, int $limit = 50): array
    {
        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $user->id)
            ->where('account_id', $accountId)
            ->orderBy('imported_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($snapshots->isEmpty()) {
            return [];
        }

        /** @var array<string, array<string, mixed>> $previousLinesByIsin */
        $previousLinesByIsin = [];
        $entries = [];

        foreach ($snapshots as $snapshot) {
            $importedAt = $this->resolveImportedAt($snapshot->imported_at);
            $totalValue = (float) $snapshot->total_market_value;
            $previousTotal = $entries !== [] ? $entries[count($entries) - 1]['total_market_value'] : null;

            $changeAmount = $previousTotal !== null ? $totalValue - $previousTotal : null;
            $changePct = null;

            if ($changeAmount !== null && $previousTotal > 0) {
                $changePct = ($changeAmount / $previousTotal) * 100;
            }

            $lines = [];

            foreach ($snapshot->lines as $line) {
                if (! is_array($line)) {
                    continue;
                }

                $isin = (string) ($line['isin'] ?? '');
                $quantity = (float) ($line['quantity'] ?? 0);
                $marketValue = (float) ($line['market_value'] ?? 0);
                $previousLine = $previousLinesByIsin[$isin] ?? null;

                $lines[] = [
                    'isin' => $isin,
                    'label' => (string) ($line['label'] ?? ''),
                    'quantity' => $quantity,
                    'average_price' => (float) ($line['average_price'] ?? 0),
                    'last_price' => array_key_exists('last_price', $line) && $line['last_price'] !== null
                        ? (float) $line['last_price']
                        : null,
                    'market_value' => $marketValue,
                    'quantity_delta' => $previousLine !== null
                        ? $quantity - (float) ($previousLine['quantity'] ?? 0)
                        : null,
                    'value_delta' => $previousLine !== null
                        ? $marketValue - (float) ($previousLine['market_value'] ?? 0)
                        : null,
                ];
            }

            usort($lines, static fn (array $a, array $b): int => strcmp($a['isin'], $b['isin']));

            $entries[] = [
                'id' => $snapshot->id,
                'imported_at' => $importedAt->toIso8601String(),
                'label' => $importedAt->format('d/m/Y H:i:s'),
                'total_market_value' => $totalValue,
                'positions_count' => $snapshot->positions_count,
                'change_amount' => $changeAmount,
                'change_pct' => $changePct,
                'original_filename' => $snapshot->original_filename,
                'lines' => $lines,
            ];

            $previousLinesByIsin = [];

            foreach ($snapshot->lines as $line) {
                if (is_array($line) && isset($line['isin'])) {
                    $previousLinesByIsin[(string) $line['isin']] = $line;
                }
            }
        }

        return array_reverse($entries);
    }

    /**
     * Snapshots for the same CSV file (provider + filename) — re-upload history.
     *
     * @return list<array{
     *     id: int,
     *     imported_at: string,
     *     label: string,
     *     total_market_value: float,
     *     positions_count: int,
     *     change_pct: float|null,
     * }>
     */
    public function historyForFileSignature(User $user, string $fileSignature, int $limit = 12): array
    {
        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $user->id)
            ->where('file_signature', $fileSignature)
            ->orderByDesc('imported_at')
            ->limit($limit)
            ->get()
            ->sortBy('imported_at')
            ->values();

        $previousValue = null;
        $history = [];

        foreach ($snapshots as $snapshot) {
            $value = (float) $snapshot->total_market_value;
            $changePct = null;

            if ($previousValue !== null && $previousValue > 0) {
                $changePct = (($value - $previousValue) / $previousValue) * 100;
            }

            $importedAt = $this->resolveImportedAt($snapshot->imported_at);

            $history[] = [
                'id' => $snapshot->id,
                'imported_at' => $importedAt->toIso8601String(),
                'label' => $importedAt->format('d/m/Y H:i'),
                'total_market_value' => $value,
                'positions_count' => $snapshot->positions_count,
                'change_pct' => $changePct,
            ];

            $previousValue = $value;
        }

        return $history;
    }

    public function deleteSnapshot(User $user, PortfolioSnapshot $snapshot): void
    {
        if ($snapshot->user_id !== $user->id) {
            throw new \InvalidArgumentException('portfolio_snapshot_forbidden');
        }

        $account = $snapshot->account;

        if ($account === null) {
            throw new \InvalidArgumentException('portfolio_snapshot_invalid');
        }

        $restorePositions = $this->isLatestSnapshotForAccount($snapshot);

        DB::transaction(function () use ($user, $account, $snapshot, $restorePositions): void {
            if ($restorePositions) {
                $previous = PortfolioSnapshot::query()
                    ->where('user_id', $user->id)
                    ->where('account_id', $account->id)
                    ->whereKeyNot($snapshot->id)
                    ->orderByDesc('imported_at')
                    ->orderByDesc('id')
                    ->first();

                $this->restorePositionsFromLines(
                    $user,
                    $account,
                    $previous !== null ? $previous->lines : null,
                );
            }

            $snapshot->delete();
        });

        $this->netWorthSnapshots->recordForUser($user);
    }

    private function isLatestSnapshotForAccount(PortfolioSnapshot $snapshot): bool
    {
        $importedAt = $this->resolveImportedAt($snapshot->imported_at);

        return ! PortfolioSnapshot::query()
            ->where('user_id', $snapshot->user_id)
            ->where('account_id', $snapshot->account_id)
            ->where(function ($query) use ($snapshot, $importedAt): void {
                $query->where('imported_at', '>', $importedAt)
                    ->orWhere(function ($inner) use ($snapshot, $importedAt): void {
                        $inner->where('imported_at', $importedAt)
                            ->where('id', '>', $snapshot->id);
                    });
            })
            ->exists();
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $lines
     */
    /**
     * Account total market value at each import snapshot (for positions list chart).
     *
     * @return list<array{ date: string, value: float }>
     */
    public function portfolioValueSeriesForAccount(Account $account, int $limit = 48): array
    {
        $snapshots = PortfolioSnapshot::query()
            ->where('account_id', $account->id)
            ->orderBy('imported_at')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $points = [];

        foreach ($snapshots as $snapshot) {
            $importedAt = $this->resolveImportedAt($snapshot->imported_at);

            $points[] = [
                'date' => $importedAt->toDateString(),
                'value' => (float) $snapshot->total_market_value,
            ];
        }

        return $points;
    }

    private function restorePositionsFromLines(User $user, Account $account, ?array $lines): void
    {
        Position::query()
            ->where('user_id', $user->id)
            ->where('account_id', $account->id)
            ->delete();

        if ($lines === null || $lines === []) {
            return;
        }

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $this->positions->upsertFromImport($user, $account, [
                'isin' => (string) ($line['isin'] ?? ''),
                'label' => (string) ($line['label'] ?? ''),
                'quantity' => (string) ($line['quantity'] ?? '0'),
                'average_price' => (string) ($line['average_price'] ?? '0'),
                'last_price' => array_key_exists('last_price', $line) && $line['last_price'] !== null
                    ? (string) $line['last_price']
                    : null,
            ]);
        }
    }

    private function resolveImportedAt(mixed $importedAt): CarbonImmutable
    {
        if ($importedAt instanceof CarbonImmutable) {
            return $importedAt;
        }

        return CarbonImmutable::parse((string) $importedAt);
    }
}
