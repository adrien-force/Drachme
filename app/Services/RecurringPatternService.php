<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\RecurringSuggestion;
use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\DismissedRecurringPattern;
use App\Models\RecurringPattern;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RecurringLabelNormalizer;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class RecurringPatternService
{
    public function __construct(
        private readonly RecurringLabelNormalizer $labelNormalizer,
    ) {}

    public function confirm(User $user, RecurringSuggestion $suggestion, ?int $categoryId = null): RecurringPattern
    {
        $resolvedCategoryId = $categoryId ?? $suggestion->suggestedCategoryId;

        if ($resolvedCategoryId !== null) {
            $exists = Category::query()
                ->where('user_id', $user->id)
                ->whereKey($resolvedCategoryId)
                ->exists();

            if (! $exists) {
                throw new InvalidArgumentException('recurring_category_forbidden');
            }
        }

        $lastSeen = $suggestion->sampleTransactions !== []
            ? CarbonImmutable::parse($suggestion->sampleTransactions[array_key_last($suggestion->sampleTransactions)]->date)
            : CarbonImmutable::today();

        return RecurringPattern::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'label_pattern' => $suggestion->labelPattern,
                'transaction_type' => $suggestion->transactionType,
            ],
            [
                'display_label' => $suggestion->displayLabel,
                'expected_amount' => $suggestion->expectedAmount,
                'frequency' => $suggestion->frequency,
                'category_id' => $resolvedCategoryId,
                'account_id' => $suggestion->accountId,
                'occurrence_count' => $suggestion->occurrenceCount,
                'last_seen_at' => $lastSeen->toDateString(),
                'is_confirmed' => true,
            ],
        );
    }

    public function confirmFromTransaction(
        User $user,
        Transaction $transaction,
        RecurringFrequency $frequency,
    ): RecurringPattern {
        if ($transaction->user_id !== $user->id) {
            throw new InvalidArgumentException('recurring_transaction_forbidden');
        }

        if ($transaction->transfer_pair_id !== null) {
            throw new InvalidArgumentException('recurring_transfer_forbidden');
        }

        $type = $transaction->type;
        $typeValue = $type instanceof TransactionType ? $type->value : (string) $type;

        if ($typeValue === TransactionType::Transfer->value) {
            throw new InvalidArgumentException('recurring_transfer_forbidden');
        }

        $labelPattern = $this->labelNormalizer->normalize($transaction->label);

        if ($this->labelNormalizer->isGeneric($labelPattern)) {
            throw new InvalidArgumentException('recurring_label_generic');
        }

        $amount = abs((float) $transaction->amount);

        if ($amount < 0.01) {
            throw new InvalidArgumentException('recurring_amount_zero');
        }

        $transactionType = $transaction->type instanceof TransactionType
            ? $transaction->type
            : TransactionType::from($typeValue);

        $suggestion = new RecurringSuggestion(
            labelPattern: $labelPattern,
            displayLabel: $transaction->label,
            expectedAmount: number_format($amount, 2, '.', ''),
            frequency: $frequency,
            transactionType: $transactionType,
            occurrenceCount: 1,
            score: 100,
            suggestedCategoryId: $transaction->category_id,
            accountId: $transaction->account_id,
            sampleTransactions: [$transaction],
        );

        return $this->confirm($user, $suggestion, $transaction->category_id);
    }

    public function removeForTransaction(User $user, Transaction $transaction): void
    {
        if ($transaction->user_id !== $user->id) {
            throw new InvalidArgumentException('recurring_transaction_forbidden');
        }

        $pattern = $this->matchesConfirmedPattern($user, $transaction);

        if ($pattern === null) {
            throw new InvalidArgumentException('recurring_not_found');
        }

        $pattern->delete();
    }

    public function dismiss(User $user, string $labelPattern, TransactionType $transactionType): void
    {
        DismissedRecurringPattern::query()->firstOrCreate([
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
            'transaction_type' => $transactionType,
        ]);
    }

    /**
     * @return list<RecurringPattern>
     */
    public function confirmedForUser(User $user): array
    {
        /** @var list<RecurringPattern> $patterns */
        $patterns = array_values(
            RecurringPattern::query()
                ->where('user_id', $user->id)
                ->where('is_confirmed', true)
                ->with(['category:id,name,color', 'account:id,name,logo_path'])
                ->orderBy('display_label')
                ->get()
                ->all(),
        );

        return $patterns;
    }

    public function matchesConfirmedPattern(User $user, Transaction $transaction): ?RecurringPattern
    {
        return $this->matchIndexed(
            $transaction,
            $this->confirmedPatternsIndexed($user),
        );
    }

    /** Keeps confirmed pattern category aligned with a matching transaction. */
    public function syncCategoryFromTransaction(User $user, Transaction $transaction): void
    {
        $pattern = $this->matchesConfirmedPattern($user, $transaction);

        if ($pattern === null) {
            return;
        }

        $categoryId = $transaction->category_id;

        if ($categoryId !== null) {
            $exists = Category::query()
                ->where('user_id', $user->id)
                ->whereKey($categoryId)
                ->exists();

            if (! $exists) {
                return;
            }
        }

        if ($pattern->category_id === $categoryId) {
            return;
        }

        $pattern->update(['category_id' => $categoryId]);
    }

    /**
     * @return array<string, RecurringPattern>
     */
    public function confirmedPatternsIndexed(User $user): array
    {
        $patterns = RecurringPattern::query()
            ->where('user_id', $user->id)
            ->where('is_confirmed', true)
            ->get();

        $indexed = [];

        foreach ($patterns as $pattern) {
            $type = $pattern->transaction_type instanceof TransactionType
                ? $pattern->transaction_type->value
                : (string) $pattern->transaction_type;
            $indexed[$pattern->label_pattern.'|'.$type] = $pattern;
        }

        return $indexed;
    }

    /**
     * @param  array<string, RecurringPattern>  $indexed
     */
    public function matchIndexed(Transaction $transaction, array $indexed): ?RecurringPattern
    {
        $patternKey = $this->labelNormalizer->normalize($transaction->label);

        if ($this->labelNormalizer->isGeneric($patternKey)) {
            return null;
        }

        $transactionType = $transaction->type instanceof TransactionType
            ? $transaction->type
            : TransactionType::from((string) $transaction->type);
        $recurring = $indexed[$patternKey.'|'.$transactionType->value] ?? null;

        if ($recurring === null) {
            return null;
        }

        $expected = abs((float) $recurring->expected_amount);
        $actual = abs((float) $transaction->amount);

        if ($expected < 0.01) {
            return null;
        }

        if (abs($actual - $expected) / $expected > 0.05) {
            return null;
        }

        return $recurring;
    }
}
