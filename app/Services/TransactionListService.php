<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionList\TransactionListFilterApplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TransactionListService
{
    public function __construct(
        private readonly TransactionListFilterApplier $filters,
    ) {}

    /**
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
     *     sort?: string,
     *     order?: string,
     *     per_page?: int,
     *     page?: int,
     * }  $filterParams
     *
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function paginateForUser(User $user, array $filterParams): LengthAwarePaginator
    {
        $query = $this->filteredQueryForUser($user, $filterParams)
            ->with(['user:id', 'account:id,name,logo_path', 'category:id,name,color']);

        $this->filters->applySorting($query, $filterParams);

        $perPage = $filterParams['per_page'] ?? 50;
        $page = $filterParams['page'] ?? 1;

        return $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * Sum of amounts for all rows matching the filters (ignores pagination and sort).
     *
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
     * }  $filterParams
     */
    public function sumAmountForUser(User $user, array $filterParams): float
    {
        $sum = $this->filteredQueryForUser($user, $filterParams)->sum('transactions.amount');

        return (float) $sum;
    }

    /**
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
     * }  $filterParams
     *
     * @return Builder<Transaction>
     */
    private function filteredQueryForUser(User $user, array $filterParams): Builder
    {
        $query = Transaction::query()->where('transactions.user_id', $user->id);
        $this->filters->apply($query, $user, $filterParams);

        return $query;
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     date_from?: string|null,
     *     date_to?: string|null,
     *     type?: string|null,
     *     flow?: string|null,
     *     category_id?: int|string|null,
     *     amount_min?: float|string|null,
     *     amount_max?: float|string|null,
     *     sort?: string,
     *     order?: string,
     *     per_page?: int,
     *     page?: int,
     * }  $filterParams
     *
     * @return LengthAwarePaginator<int, Transaction>
     */
    public function paginateForAccount(Account $account, array $filterParams): LengthAwarePaginator
    {
        $query = Transaction::query()
            ->with('category:id,name,color')
            ->where('account_id', $account->id);

        $user = $account->user;
        if ($user === null) {
            /** @var LengthAwarePaginator<int, Transaction> */
            return $query->whereRaw('1 = 0')->paginate(25);
        }

        $this->filters->apply($query, $user, $filterParams);
        $this->filters->applySorting($query, $filterParams);

        $perPage = $filterParams['per_page'] ?? 25;
        $page = $filterParams['page'] ?? 1;

        return $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
