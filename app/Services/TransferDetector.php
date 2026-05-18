<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\TransferSuggestion;
use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\DismissedTransferSuggestion;
use App\Models\Transaction;
use App\Models\User;
use App\Support\SettlementLabelMatcher;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TransferDetector
{
    private const DEFAULT_DAYS_WINDOW = 3;

    private const CREDIT_CARD_SETTLEMENT_DAYS_WINDOW = 7;

    /**
     * @return list<TransferSuggestion>
     */
    public function findCandidates(User $user, int $daysWindow = self::DEFAULT_DAYS_WINDOW): array
    {
        /** @var Collection<int, Transaction> $transactions */
        $transactions = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('transfer_pair_id')
            ->whereIn('type', [TransactionType::Expense, TransactionType::Income])
            ->with(['account:id,name,logo_path,type,settlement_account_id'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        if ($transactions->count() < 2) {
            return [];
        }

        /** @var array<int, Account> $accountsById */
        $accountsById = Account::query()
            ->where('user_id', $user->id)
            ->get(['id', 'type', 'settlement_account_id', 'settlement_label_pattern'])
            ->keyBy('id')
            ->all();

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

                $pairDaysWindow = $this->daysWindowForPair($outgoing, $incoming, $accountsById, $daysWindow);

                if ($this->daysApart($outgoing, $incoming) > $pairDaysWindow) {
                    continue;
                }

                [$canonicalA, $canonicalB] = DismissedTransferSuggestion::canonicalPairIds(
                    $outgoing->id,
                    $incoming->id,
                );

                if (isset($dismissedKeys[$canonicalA.':'.$canonicalB])) {
                    continue;
                }

                $score = $this->scoreLabelMatch($outgoing->label, $incoming->label)
                    + $this->scoreCreditCardSettlement($outgoing, $incoming, $accountsById)
                    + $this->scoreConfiguredSettlementLabel($outgoing, $incoming, $accountsById)
                    + $this->scoreSettlementLabel($outgoing->label)
                    + $this->scoreSettlementLabel($incoming->label);

                $suggestions[] = new TransferSuggestion(
                    outgoing: $outgoing,
                    incoming: $incoming,
                    score: $score,
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

    /**
     * @param  array<int, Account>  $accountsById
     */
    private function daysWindowForPair(
        Transaction $outgoing,
        Transaction $incoming,
        array $accountsById,
        int $defaultWindow,
    ): int {
        if ($this->isCreditCardSettlementPair($outgoing, $incoming, $accountsById)) {
            return max($defaultWindow, self::CREDIT_CARD_SETTLEMENT_DAYS_WINDOW);
        }

        return $defaultWindow;
    }

    /**
     * @param  array<int, Account>  $accountsById
     */
    private function scoreCreditCardSettlement(
        Transaction $outgoing,
        Transaction $incoming,
        array $accountsById,
    ): int {
        return $this->isCreditCardSettlementPair($outgoing, $incoming, $accountsById) ? 80 : 0;
    }

    /**
     * @param  array<int, Account>  $accountsById
     */
    private function isCreditCardSettlementPair(
        Transaction $outgoing,
        Transaction $incoming,
        array $accountsById,
    ): bool {
        $outAccount = $accountsById[$outgoing->account_id] ?? null;
        $inAccount = $accountsById[$incoming->account_id] ?? null;

        if ($outAccount === null || $inAccount === null) {
            return false;
        }

        $outType = $outAccount->type instanceof AccountType
            ? $outAccount->type
            : AccountType::from((string) $outAccount->type);
        $inType = $inAccount->type instanceof AccountType
            ? $inAccount->type
            : AccountType::from((string) $inAccount->type);

        if (
            $inType === AccountType::CreditCard
            && (int) $inAccount->settlement_account_id === $outgoing->account_id
            && (float) $outgoing->amount < 0
            && (float) $incoming->amount > 0
        ) {
            return true;
        }

        if (
            $outType === AccountType::CreditCard
            && (int) $outAccount->settlement_account_id === $incoming->account_id
            && (float) $incoming->amount < 0
            && (float) $outgoing->amount > 0
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, Account>  $accountsById
     */
    private function scoreConfiguredSettlementLabel(
        Transaction $outgoing,
        Transaction $incoming,
        array $accountsById,
    ): int {
        if (! $this->isCreditCardSettlementPair($outgoing, $incoming, $accountsById)) {
            return 0;
        }

        $cardAccount = $this->creditCardAccountFromPair($outgoing, $incoming, $accountsById);

        if ($cardAccount === null) {
            return 0;
        }

        $checkingTransaction = $outgoing->account_id === (int) $cardAccount->settlement_account_id
            ? $outgoing
            : $incoming;

        if (SettlementLabelMatcher::matches($cardAccount->settlement_label_pattern, $checkingTransaction->label)) {
            return 90;
        }

        return 0;
    }

    /**
     * @param  array<int, Account>  $accountsById
     */
    private function creditCardAccountFromPair(
        Transaction $outgoing,
        Transaction $incoming,
        array $accountsById,
    ): ?Account {
        foreach ([$outgoing, $incoming] as $transaction) {
            $account = $accountsById[$transaction->account_id] ?? null;

            if ($account === null) {
                continue;
            }

            $type = $account->type instanceof AccountType
                ? $account->type
                : AccountType::from((string) $account->type);

            if ($type === AccountType::CreditCard) {
                return $account;
            }
        }

        return null;
    }

    private function scoreSettlementLabel(string $label): int
    {
        return SettlementLabelMatcher::matchesGenericKeywords($label) ? 25 : 0;
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
