<?php

declare(strict_types=1);

namespace App\Services\TransactionList;

use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Database\Eloquent\Builder;

class TransactionListFilterApplier
{
    public function __construct(
        private readonly CategoryService $categories,
    ) {}

    /**
     * @param  Builder<\App\Models\Transaction>  $query
     * @param  array{
     *     search?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     type?: string|null,
     *     flow?: string|null,
     *     category_id?: int|string|null,
     *     account_id?: int|string|null,
     *     amount_min?: float|string|null,
     *     amount_max?: float|string|null,
     * }  $filters
     */
    public function apply(Builder $query, User $user, array $filters): void
    {
        if (! empty($filters['search'])) {
            $term = '%'.addcslashes(mb_strtolower((string) $filters['search']), '%_\\').'%';
            $query->whereRaw('LOWER(label) LIKE ?', [$term]);
        }

        if (! empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (($filters['flow'] ?? null) === 'credit') {
            $query->where('amount', '>', 0);
        }

        if (($filters['flow'] ?? null) === 'debit') {
            $query->where('amount', '<', 0);
        }

        $accountId = $filters['account_id'] ?? null;
        if (is_numeric($accountId)) {
            $query->where('account_id', (int) $accountId);
        }

        if (array_key_exists('amount_min', $filters) && $filters['amount_min'] !== null) {
            $query->where('amount', '>=', (float) $filters['amount_min']);
        }

        if (array_key_exists('amount_max', $filters) && $filters['amount_max'] !== null) {
            $query->where('amount', '<=', (float) $filters['amount_max']);
        }

        $this->applyCategoryFilter($query, $user, $filters['category_id'] ?? null);
    }

    /**
     * @param  Builder<\App\Models\Transaction>  $query
     * @param  array{sort?: string, order?: string}  $filters
     */
    public function applySorting(Builder $query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'date';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'label' => $query->orderBy('label', $order)->orderBy('id', $order),
            'amount' => $query->orderBy('amount', $order)->orderBy('id', $order),
            'type' => $query->orderBy('type', $order)->orderBy('id', $order),
            'account' => $query
                ->leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                ->orderBy('accounts.name', $order)
                ->select('transactions.*')
                ->orderBy('transactions.id', $order),
            'category' => $query
                ->leftJoin('categories', 'transactions.category_id', '=', 'categories.id')
                ->orderBy('categories.name', $order)
                ->select('transactions.*')
                ->orderBy('transactions.id', $order),
            default => $query->orderBy('date', $order)->orderBy('id', $order),
        };
    }

    /**
     * @param  Builder<\App\Models\Transaction>  $query
     */
    private function applyCategoryFilter(Builder $query, User $user, mixed $categoryFilter): void
    {
        if ($categoryFilter === 'uncategorized') {
            $query->whereNull('category_id');

            return;
        }

        if (! is_numeric($categoryFilter)) {
            return;
        }

        $categoryId = (int) $categoryFilter;
        $ids = $this->categories->selfAndDescendantIds($user, $categoryId);

        if ($ids === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('category_id', $ids);
    }
}
