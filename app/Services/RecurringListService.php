<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\RecurringSuggestion;
use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\RecurringPattern;
use App\Models\User;
use App\Support\RecurringMonthlyAmount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class RecurringListService
{
    /**
     * @param  array{
     *     search?: string|null,
     *     flow?: string|null,
     *     frequency?: string|null,
     *     sort?: string,
     *     order?: string,
     *     per_page?: int,
     *     confirmed_page?: int,
     * }  $filters
     *
     * @return LengthAwarePaginator<int, RecurringPattern>
     */
    public function paginateConfirmed(User $user, array $filters): LengthAwarePaginator
    {
        $query = RecurringPattern::query()
            ->where('user_id', $user->id)
            ->where('is_confirmed', true)
            ->with(['category:id,name,color', 'account:id,name,logo_path']);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 25;
        $page = $filters['confirmed_page'] ?? 1;

        return $query
            ->paginate($perPage, ['*'], 'confirmed_page', $page)
            ->withQueryString();
    }

    /**
     * @param  list<RecurringSuggestion>  $suggestions
     * @param  array{
     *     search?: string|null,
     *     flow?: string|null,
     *     frequency?: string|null,
     *     sort?: string,
     *     order?: string,
     *     per_page?: int,
     *     suggestions_page?: int,
     * }  $filters
     *
     * @return LengthAwarePaginator<int, RecurringSuggestion>
     */
    public function paginateSuggestions(User $user, array $suggestions, array $filters): LengthAwarePaginator
    {
        $filtered = $this->filterSuggestions($suggestions, $filters);
        $sorted = $this->sortSuggestions($filtered, $filters);

        $perPage = $filters['per_page'] ?? 25;
        $page = $filters['suggestions_page'] ?? 1;
        $total = count($sorted);
        $items = array_slice($sorted, ($page - 1) * $perPage, $perPage);

        return new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => 'suggestions_page',
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     flow?: string|null,
     *     frequency?: string|null,
     * }  $filters
     *
     * @return array{
     *     monthly_expense: string,
     *     monthly_income: string,
     *     confirmed_count: int,
     *     expense_count: int,
     *     income_count: int,
     *     by_frequency: list<array{frequency: string, count: int}>,
     *     top_items: list<array{label: string, monthly_amount: string, transaction_type: string}>,
     * }
     */
    public function buildSummary(User $user, array $filters): array
    {
        $query = RecurringPattern::query()
            ->where('user_id', $user->id)
            ->where('is_confirmed', true);

        $this->applyFilters($query, $filters);

        /** @var Collection<int, RecurringPattern> $patterns */
        $patterns = $query->get();

        $monthlyExpense = 0.0;
        $monthlyIncome = 0.0;
        $expenseCount = 0;
        $incomeCount = 0;
        $frequencyCounts = [];
        $topItems = [];

        foreach ($patterns as $pattern) {
            $type = $pattern->transaction_type instanceof TransactionType
                ? $pattern->transaction_type
                : TransactionType::from((string) $pattern->transaction_type);
            $frequency = $pattern->frequency instanceof RecurringFrequency
                ? $pattern->frequency
                : RecurringFrequency::from((string) $pattern->frequency);
            $monthly = RecurringMonthlyAmount::normalize((float) $pattern->expected_amount, $frequency);
            $frequencyKey = $frequency->value;
            $frequencyCounts[$frequencyKey] = ($frequencyCounts[$frequencyKey] ?? 0) + 1;

            if ($type === TransactionType::Income) {
                $monthlyIncome += $monthly;
                $incomeCount++;
            } else {
                $monthlyExpense += $monthly;
                $expenseCount++;
            }

            $topItems[] = [
                'label' => $pattern->display_label,
                'monthly_amount' => number_format($monthly, 2, '.', ''),
                'transaction_type' => $type->value,
            ];
        }

        usort(
            $topItems,
            static fn (array $a, array $b): int => (float) $b['monthly_amount'] <=> (float) $a['monthly_amount'],
        );

        $byFrequency = [];

        foreach (RecurringFrequency::cases() as $frequency) {
            $count = $frequencyCounts[$frequency->value] ?? 0;

            if ($count > 0) {
                $byFrequency[] = [
                    'frequency' => $frequency->value,
                    'count' => $count,
                ];
            }
        }

        return [
            'monthly_expense' => number_format($monthlyExpense, 2, '.', ''),
            'monthly_income' => number_format($monthlyIncome, 2, '.', ''),
            'confirmed_count' => $patterns->count(),
            'expense_count' => $expenseCount,
            'income_count' => $incomeCount,
            'by_frequency' => $byFrequency,
            'top_items' => array_slice($topItems, 0, 6),
        ];
    }

    /**
     * @param  Builder<RecurringPattern>  $query
     * @param  array{
     *     search?: string|null,
     *     flow?: string|null,
     *     frequency?: string|null,
     * }  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $search = $filters['search'] ?? null;

        if (is_string($search) && $search !== '') {
            $needle = '%'.addcslashes($search, '%_').'%';
            $query->where(function (Builder $builder) use ($needle): void {
                $builder
                    ->where('display_label', 'like', $needle)
                    ->orWhere('label_pattern', 'like', $needle);
            });
        }

        $flow = $filters['flow'] ?? null;

        if ($flow === 'debit') {
            $query->where('transaction_type', TransactionType::Expense);
        } elseif ($flow === 'credit') {
            $query->where('transaction_type', TransactionType::Income);
        }

        $frequency = $filters['frequency'] ?? null;

        if (is_string($frequency) && $frequency !== '') {
            $query->where('frequency', $frequency);
        }
    }

    /**
     * @param  Builder<RecurringPattern>  $query
     * @param  array{sort?: string, order?: string}  $filters
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'amount';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $column = match ($sort) {
            'label' => 'display_label',
            'frequency' => 'frequency',
            'occurrences' => 'occurrence_count',
            'last_seen' => 'last_seen_at',
            default => 'expected_amount',
        };

        $query->orderBy($column, $order)->orderBy('display_label');
    }

    /**
     * @param  list<RecurringSuggestion>  $suggestions
     * @param  array{
     *     search?: string|null,
     *     flow?: string|null,
     *     frequency?: string|null,
     * }  $filters
     *
     * @return list<RecurringSuggestion>
     */
    private function filterSuggestions(array $suggestions, array $filters): array
    {
        return array_values(array_filter(
            $suggestions,
            function (RecurringSuggestion $suggestion) use ($filters): bool {
                $search = $filters['search'] ?? null;

                if (is_string($search) && $search !== '') {
                    $needle = mb_strtolower($search);

                    if (
                        ! str_contains(mb_strtolower($suggestion->displayLabel), $needle)
                        && ! str_contains(mb_strtolower($suggestion->labelPattern), $needle)
                    ) {
                        return false;
                    }
                }

                $flow = $filters['flow'] ?? null;

                if ($flow === 'debit' && $suggestion->transactionType !== TransactionType::Expense) {
                    return false;
                }

                if ($flow === 'credit' && $suggestion->transactionType !== TransactionType::Income) {
                    return false;
                }

                $frequency = $filters['frequency'] ?? null;

                if (
                    is_string($frequency)
                    && $frequency !== ''
                    && $suggestion->frequency->value !== $frequency
                ) {
                    return false;
                }

                return true;
            },
        ));
    }

    /**
     * @param  list<RecurringSuggestion>  $suggestions
     * @param  array{sort?: string, order?: string}  $filters
     *
     * @return list<RecurringSuggestion>
     */
    private function sortSuggestions(array $suggestions, array $filters): array
    {
        $sort = $filters['sort'] ?? 'amount';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 1 : -1;

        usort(
            $suggestions,
            static function (RecurringSuggestion $a, RecurringSuggestion $b) use ($sort, $order): int {
                $result = match ($sort) {
                    'label' => strcasecmp($a->displayLabel, $b->displayLabel),
                    'frequency' => $a->frequency->value <=> $b->frequency->value,
                    'occurrences' => $a->occurrenceCount <=> $b->occurrenceCount,
                    default => (float) $a->expectedAmount <=> (float) $b->expectedAmount,
                };

                return $result * $order;
            },
        );

        return $suggestions;
    }
}
