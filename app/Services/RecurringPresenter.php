<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\RecurringSuggestion;
use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\RecurringPattern;
use App\Models\Transaction;
use App\Models\User;
use App\Support\CategoryDisplayName;
use App\Support\RecurringMonthlyAmount;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
            'transaction_type' => $suggestion->transactionType->value,
            'signed_amount' => $this->formatSignedAmount(
                $suggestion->expectedAmount,
                $suggestion->transactionType,
            ),
            'monthly_amount' => number_format(
                RecurringMonthlyAmount::normalize(
                    (float) $suggestion->expectedAmount,
                    $suggestion->frequency,
                ),
                2,
                '.',
                '',
            ),
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

        return $this->categories->flatSelectableOptions($user);
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
            'transaction_type' => $this->transactionTypeValue($pattern->transaction_type),
            'signed_amount' => $this->formatSignedAmount(
                (string) $pattern->expected_amount,
                $pattern->transaction_type instanceof TransactionType
                    ? $pattern->transaction_type
                    : TransactionType::from((string) $pattern->transaction_type),
            ),
            'monthly_amount' => number_format(
                RecurringMonthlyAmount::normalize(
                    (float) $pattern->expected_amount,
                    $pattern->frequency instanceof RecurringFrequency
                        ? $pattern->frequency
                        : RecurringFrequency::from((string) $pattern->frequency),
                ),
                2,
                '.',
                '',
            ),
            'occurrence_count' => $pattern->occurrence_count,
            'last_seen_at' => $pattern->last_seen_at !== null
                ? CarbonImmutable::parse($pattern->last_seen_at)->toDateString()
                : null,
            'category_id' => $pattern->category_id,
            'category_name' => $pattern->category !== null
                ? CategoryDisplayName::forCategory($pattern->category)
                : null,
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
            'type' => $transaction->type instanceof TransactionType
                ? $transaction->type->value
                : (string) $transaction->type,
            'account_name' => $account !== null ? $account->name : '',
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, RecurringPattern>  $paginator
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|null>}
     */
    public function serializeConfirmedPaginator(LengthAwarePaginator $paginator): array
    {
        /** @var list<RecurringPattern> $items */
        $items = array_values($paginator->items());

        return [
            'data' => $this->serializeConfirmed($items),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, RecurringSuggestion>  $paginator
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, int|null>}
     */
    public function serializeSuggestionsPaginator(LengthAwarePaginator $paginator): array
    {
        /** @var list<RecurringSuggestion> $items */
        $items = array_values($paginator->items());

        return [
            'data' => $this->serializeSuggestions($items),
            'meta' => $this->paginationMeta($paginator),
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     *
     * @return array<string, int|null>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function formatSignedAmount(string $absAmount, TransactionType $type): string
    {
        return number_format(
            RecurringMonthlyAmount::signed((float) $absAmount, $type),
            2,
            '.',
            '',
        );
    }

    private function frequencyValue(RecurringFrequency|string $frequency): string
    {
        return $frequency instanceof RecurringFrequency ? $frequency->value : $frequency;
    }

    private function transactionTypeValue(TransactionType|string $type): string
    {
        return $type instanceof TransactionType ? $type->value : $type;
    }
}
