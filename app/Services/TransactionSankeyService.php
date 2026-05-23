<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionList\TransactionListFilterApplier;
use App\Support\CategoryDisplayName;
use Illuminate\Support\Facades\DB;

class TransactionSankeyService
{
    public function __construct(
        private readonly TransactionListFilterApplier $filters,
    ) {}

    /**
     * Account → category flows for all transactions matching list filters (no pagination).
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
     *
     * @return array{
     *     nodes: list<array{
     *         name: string,
     *         category: 'source'|'outcome',
     *         color: string|null,
     *         kind: 'account'|'category',
     *     }>,
     *     links: list<array{source: int, target: int, value: float}>,
     * }
     */
    public function buildForUser(User $user, array $filterParams): array
    {
        $query = Transaction::query()
            ->where('transactions.user_id', $user->id);

        $this->filters->apply($query, $user, $filterParams);

        $rows = $query
            ->join('accounts', 'accounts.id', '=', 'transactions.account_id')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->select([
                'transactions.account_id',
                'accounts.name as account_name',
                'transactions.category_id',
                'categories.name as category_name',
                'categories.slug as category_slug',
                'categories.color as category_color',
                DB::raw('SUM(ABS(transactions.amount)) as total'),
            ])
            ->groupBy(
                'transactions.account_id',
                'accounts.name',
                'transactions.category_id',
                'categories.name',
                'categories.slug',
                'categories.color',
            )
            ->havingRaw('SUM(ABS(transactions.amount)) > 0')
            ->get();

        if ($rows->isEmpty()) {
            return ['nodes' => [], 'links' => []];
        }

        $uncategorizedLabel = (string) __('ui.transactions.category_none');
        $accountColor = 'var(--chart-secondary)';

        /** @var array<int, int> $accountIndex */
        $accountIndex = [];
        /** @var array<string, int> $categoryIndex */
        $categoryIndex = [];
        /** @var list<array{name: string, category: 'source'|'outcome', color: string|null, kind: 'account'|'category'}> $nodes */
        $nodes = [];
        /** @var list<array{source: int, target: int, value: float}> $links */
        $links = [];

        foreach ($rows as $row) {
            $accountId = (int) $row->account_id;
            if (! isset($accountIndex[$accountId])) {
                $accountIndex[$accountId] = count($nodes);
                $nodes[] = [
                    'name' => (string) $row->account_name,
                    'category' => 'source',
                    'color' => $accountColor,
                    'kind' => 'account',
                ];
            }

            $categoryKey = $row->category_id !== null
                ? 'cat:'.(int) $row->category_id
                : 'cat:uncategorized';

            if (! isset($categoryIndex[$categoryKey])) {
                $categoryIndex[$categoryKey] = count($nodes);
                $nodes[] = [
                    'name' => $row->category_name !== null
                        ? CategoryDisplayName::for(
                            $row->category_slug !== null ? (string) $row->category_slug : null,
                            (string) $row->category_name,
                        )
                        : $uncategorizedLabel,
                    'category' => 'outcome',
                    'color' => $row->category_color !== null
                        ? (string) $row->category_color
                        : null,
                    'kind' => 'category',
                ];
            }

            $links[] = [
                'source' => $accountIndex[$accountId],
                'target' => $categoryIndex[$categoryKey],
                'value' => round((float) $row->total, 2),
            ];
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
        ];
    }
}
