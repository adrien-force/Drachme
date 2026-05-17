<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\RecurringSuggestion;
use App\Enums\RecurringFrequency;
use App\Models\Account;
use App\Models\RecurringPattern;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;

class RecurringPresenter
{
    public function __construct(
        private readonly CategoryService $categories,
    ) {}

    /**
     * @param  list<RecurringSuggestion>  $suggestions
     *
     * @return list<array<string, mixed>>
     */
    public function serializeSuggestions(array $suggestions): array
    {
        return array_map(
            fn (RecurringSuggestion $suggestion): array => $this->serializeSuggestion($suggestion),
            $suggestions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSuggestion(RecurringSuggestion $suggestion): array
    {
        return [
            'label_pattern' => $suggestion->labelPattern,
            'display_label' => $suggestion->displayLabel,
            'expected_amount' => $suggestion->expectedAmount,
            'frequency' => $suggestion->frequency->value,
            'occurrence_count' => $suggestion->occurrenceCount,
            'score' => $suggestion->score,
            'suggested_category_id' => $suggestion->suggestedCategoryId,
            'account_id' => $suggestion->accountId,
            'samples' => array_map(
                fn (Transaction $transaction): array => $this->serializeSampleTransaction($transaction),
                $suggestion->sampleTransactions,
            ),
        ];
    }

    /**
     * @param  list<RecurringPattern>  $patterns
     *
     * @return list<array<string, mixed>>
     */
    public function serializeConfirmed(array $patterns): array
    {
        return array_map(
            fn (RecurringPattern $pattern): array => $this->serializeConfirmedPattern($pattern),
            $patterns,
        );
    }

    /**
     * @return list<array{id: int, name: string, color: string|null}>
     */
    public function categoryOptions(User $user): array
    {
        $this->categories->seedDefaultsForUser($user);

        return $this->categories->flatSelectOptions($user);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeConfirmedPattern(RecurringPattern $pattern): array
    {
        return [
            'id' => $pattern->id,
            'label_pattern' => $pattern->label_pattern,
            'display_label' => $pattern->display_label,
            'expected_amount' => $pattern->expected_amount,
            'frequency' => $this->frequencyValue($pattern->frequency),
            'occurrence_count' => $pattern->occurrence_count,
            'last_seen_at' => $pattern->last_seen_at !== null
                ? CarbonImmutable::parse($pattern->last_seen_at)->toDateString()
                : null,
            'category_id' => $pattern->category_id,
            'category_name' => $pattern->category?->name,
            'category_color' => $pattern->category?->color,
            'account_name' => $pattern->account?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeSampleTransaction(Transaction $transaction): array
    {
        $account = $transaction->relationLoaded('account')
            ? $transaction->account
            : Account::query()->find($transaction->account_id);

        return [
            'id' => $transaction->id,
            'date' => CarbonImmutable::parse($transaction->date)->toDateString(),
            'label' => $transaction->label,
            'amount' => $transaction->amount,
            'account_name' => $account !== null ? $account->name : '',
        ];
    }

    private function frequencyValue(RecurringFrequency|string $frequency): string
    {
        return $frequency instanceof RecurringFrequency ? $frequency->value : $frequency;
    }
}
