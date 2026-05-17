<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\TransferSuggestion;
use App\Enums\TransactionType;
use App\Models\DismissedTransferSuggestion;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransferDetector
{
    /**
     * @return list<TransferSuggestion>
     */
    public function findCandidates(User $user, int $daysWindow = 3): array
    {
        /** @var Collection<int, Transaction> $transactions */
        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('transfer_pair_id')
            ->whereIn('type', [TransactionType::Expense, TransactionType::Income])
            ->with(['account:id,name,logo_path'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($transactions->count() < 2) {
            return [];
        }

        $dismissedKeys = DismissedTransferSuggestion::query()
            ->where('user_id', $user->id)
            ->get(['transaction_a_id', 'transaction_b_id'])
            ->map(static fn (DismissedTransferSuggestion $row): string => $row->transaction_a_id.':'.$row->transaction_b_id)
            ->flip();

        $suggestions = [];
        $count = $transactions->count();

        for ($i = 0; $i < $count - 1; $i++) {
            $left = $transactions->get($i);
            if (! $left instanceof Transaction) {
                continue;
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $right = $transactions->get($j);
                if (! $right instanceof Transaction) {
                    continue;
                }

                if ($left->account_id === $right->account_id) {
                    continue;
                }

                if ($this->daysApart($left, $right) > $daysWindow) {
                    continue;
                }

                if (! $this->isOppositeAmountPair($left, $right)) {
                    continue;
                }

                [$outgoing, $incoming] = (float) $left->amount < 0
                    ? [$left, $right]
                    : [$right, $left];

                if ((float) $incoming->amount <= 0 || (float) $outgoing->amount >= 0) {
                    continue;
                }

                [$canonicalA, $canonicalB] = DismissedTransferSuggestion::canonicalPairIds(
                    $outgoing->id,
                    $incoming->id,
                );

                if ($dismissedKeys->has($canonicalA.':'.$canonicalB)) {
                    continue;
                }

                $suggestions[] = new TransferSuggestion(
                    outgoing: $outgoing,
                    incoming: $incoming,
                    score: $this->scoreLabelMatch($outgoing->label, $incoming->label),
                );
            }
        }

        usort(
            $suggestions,
            static fn (TransferSuggestion $a, TransferSuggestion $b): int => $b->score <=> $a->score
                ?: CarbonImmutable::parse($b->outgoing->date)->toDateString()
                    <=> CarbonImmutable::parse($a->outgoing->date)->toDateString(),
        );

        return $suggestions;
    }

    private function daysApart(Transaction $left, Transaction $right): int
    {
        $leftDate = CarbonImmutable::parse($left->date);
        $rightDate = CarbonImmutable::parse($right->date);

        return (int) $leftDate->diffInDays($rightDate, absolute: true);
    }

    private function isOppositeAmountPair(Transaction $left, Transaction $right): bool
    {
        $leftAmount = (float) $left->amount;
        $rightAmount = (float) $right->amount;

        if (($leftAmount > 0 && $rightAmount > 0) || ($leftAmount < 0 && $rightAmount < 0)) {
            return false;
        }

        return abs(abs($leftAmount) - abs($rightAmount)) < 0.001;
    }

    private function scoreLabelMatch(string $leftLabel, string $rightLabel): int
    {
        $left = Str::lower(trim($leftLabel));
        $right = Str::lower(trim($rightLabel));

        if ($left === '' || $right === '') {
            return 0;
        }

        if ($left === $right) {
            return 100;
        }

        if (Str::contains($left, $right) || Str::contains($right, $left)) {
            return 75;
        }

        similar_text($left, $right, $percent);

        return (int) round($percent);
    }
}
