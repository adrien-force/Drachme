<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AccountTransactionListService
{
    /**
     * @param array{
     *     search?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     type?: string|null,
     *     flow?: string|null,
     *     sort?: string,
     *     order?: string,
     *     per_page?: int,
     *     page?: int,
     * } $filters
     *
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function paginate(Account $account, array $filters): LengthAwarePaginator
    {
        $query = Transaction::query()->where('account_id', $account->id);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        $perPage = $filters['per_page'] ?? 25;
        $page = $filters['page'] ?? 1;

        return $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * @param Builder<Transaction> $query
     * @param array{
     *     search?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     type?: string|null,
     *     flow?: string|null,
     * } $filters
     */
    private function applyFilters(Builder $query, array $filters): void
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
    }

    /**
     * @param Builder<Transaction> $query
     * @param array{sort?: string, order?: string} $filters
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'date';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        match ($sort) {
            'label' => $query->orderBy('label', $order)->orderBy('id', $order),
            'amount' => $query->orderBy('amount', $order)->orderBy('id', $order),
            'type' => $query->orderBy('type', $order)->orderBy('id', $order),
            default => $query->orderBy('date', $order)->orderBy('id', $order),
        };
    }
}
