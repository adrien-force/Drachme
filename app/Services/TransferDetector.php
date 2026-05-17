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

        /** @var array<string, true> $dismissedKeys */
        $dismissedKeys = DismissedTransferSuggestion::query()
            ->where('user_id', $user->id)
            ->get(['transaction_a_id', 'transaction_b_id'])
            ->mapWithKeys(static fn (DismissedTransferSuggestion $row): array => [
                $row->transaction_a_id.':'.$row->transaction_b_id => true,
            ])
            ->all();

        /** @var array<string, list<Transaction>> $incomesByAmount */
        $incomesByAmount = [];

        /** @var list<Transaction> $outgoings */
        $outgoings = [];

        foreach ($transactions as $transaction) {
            $amount = (float) $transaction->amount;

            if ($amount > 0) {
                $incomesByAmount[$this->amountKey($amount)][] = $transaction;

                continue;
            }

            if ($amount < 0) {
                $outgoings[] = $transaction;
            }
        }

        $suggestions = [];

        foreach ($outgoings as $outgoing) {
            $candidates = $incomesByAmount[$this->amountKey((float) $outgoing->amount)] ?? [];

            foreach ($candidates as $incoming) {
                if ($outgoing->account_id === $incoming->account_id) {
                    continue;
                }

                if ($this->daysApart($outgoing, $incoming) > $daysWindow) {
                    continue;
                }

                [$canonicalA, $canonicalB] = DismissedTransferSuggestion::canonicalPairIds(
                    $outgoing->id,
                    $incoming->id,
                );

                if (isset($dismissedKeys[$canonicalA.':'.$canonicalB])) {
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

    private function amountKey(float $amount): string
    {
        return number_format(abs($amount), 2, '.', '');
    }

    private function daysApart(Transaction $left, Transaction $right): int
    {
        $leftDate = CarbonImmutable::parse($left->date);
        $rightDate = CarbonImmutable::parse($right->date);

        return (int) $leftDate->diffInDays($rightDate, absolute: true);
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
