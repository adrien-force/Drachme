<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionList\TransactionListFilterApplier;
use App\Support\CategoryDisplayName;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionSankeyService
{
    private const INCOME_ROOT_SLUG = 'income';

    private const TRANSFER_ROOT_SLUG = 'transfer';

    public function __construct(
        private readonly TransactionListFilterApplier $filters,
    ) {}

    /**
     * Income sources → expense root categories → expense subcategories.
     *
     * Credits under the income tree appear on the left. Debits are aggregated on
     * root categories in the middle and direct subcategories on the right; deeper
     * categories roll up to their direct subcategory under the root.
     *
     * Left-to-middle links distribute each income source proportionally to expense
     * roots based on their share of total spending.
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
     *         category: 'source'|'landing'|'outcome',
     *         color: string|null,
     *         kind: 'account'|'category',
     *         depth?: 1|2,
     *     }>,
     *     links: list<array{source: int, target: int, value: float}>,
     * }
     */
    public function buildForUser(User $user, array $filterParams): array
    {
        /** @var Collection<int, Category> $categoriesById */
        $categoriesById = Category::query()
            ->where('user_id', $user->id)
            ->get(['id', 'parent_id', 'name', 'slug', 'color'])
            ->keyBy('id');

        if ($categoriesById->isEmpty()) {
            return ['nodes' => [], 'links' => []];
        }

        $creditRows = $this->aggregateCategoryRows($user, $filterParams, '>');
        $debitRows = $this->aggregateCategoryRows($user, $filterParams, '<');

        /** @var array<int, float> $incomeTotals */
        $incomeTotals = [];
        /** @var array<int, Category> $incomeCategories */
        $incomeCategories = [];

        foreach ($creditRows as $row) {
            $incomeCategory = $this->resolveIncomeSourceCategory(
                $row->category_id !== null ? (int) $row->category_id : null,
                $categoriesById,
            );

            if ($incomeCategory === null) {
                continue;
            }

            $total = round((float) $row->total, 2);
            if ($total <= 0) {
                continue;
            }

            $incomeCategories[$incomeCategory->id] = $incomeCategory;
            $incomeTotals[$incomeCategory->id] = ($incomeTotals[$incomeCategory->id] ?? 0) + $total;
        }

        /** @var array<int, float> $rootTotals */
        $rootTotals = [];
        /** @var array<string, float> $rootToSubTotals */
        $rootToSubTotals = [];
        /** @var array<int, Category> $rootCategories */
        $rootCategories = [];
        /** @var array<int, Category> $subCategories */
        $subCategories = [];

        foreach ($debitRows as $row) {
            $pair = $this->resolveRootAndSubCategory(
                $row->category_id !== null ? (int) $row->category_id : null,
                $categoriesById,
            );

            if ($pair === null) {
                continue;
            }

            [$root, $sub] = $pair;

            if ($this->isExcludedExpenseRoot($root)) {
                continue;
            }

            $total = round(abs((float) $row->total), 2);
            if ($total <= 0) {
                continue;
            }

            $rootCategories[$root->id] = $root;
            $rootTotals[$root->id] = ($rootTotals[$root->id] ?? 0) + $total;

            if ($sub->id !== $root->id) {
                $subCategories[$sub->id] = $sub;
                $rootSubKey = "{$root->id}:{$sub->id}";
                $rootToSubTotals[$rootSubKey] = ($rootToSubTotals[$rootSubKey] ?? 0) + $total;
            }
        }

        if ($incomeTotals === [] || $rootTotals === []) {
            return ['nodes' => [], 'links' => []];
        }

        /** @var array<int, int> $incomeIndex */
        $incomeIndex = [];
        /** @var array<int, int> $rootIndex */
        $rootIndex = [];
        /** @var array<int, int> $subIndex */
        $subIndex = [];
        /** @var list<array{name: string, category: 'source'|'landing'|'outcome', color: string|null, kind: 'account'|'category', depth?: 1|2}> $nodes */
        $nodes = [];
        /** @var list<array{source: int, target: int, value: float}> $links */
        $links = [];

        foreach ($incomeCategories as $incomeId => $incomeCategory) {
            if (($incomeTotals[$incomeId] ?? 0) <= 0) {
                continue;
            }

            $incomeIndex[$incomeId] = count($nodes);
            $nodes[] = [
                'name' => CategoryDisplayName::forCategory($incomeCategory),
                'category' => 'source',
                'color' => $incomeCategory->color,
                'kind' => 'category',
            ];
        }

        foreach ($rootCategories as $rootId => $root) {
            if (($rootTotals[$rootId] ?? 0) <= 0) {
                continue;
            }

            $rootIndex[$rootId] = count($nodes);
            $nodes[] = [
                'name' => CategoryDisplayName::forCategory($root),
                'category' => 'landing',
                'color' => $root->color,
                'kind' => 'category',
                'depth' => 1,
            ];
        }

        foreach ($subCategories as $subId => $sub) {
            $subIndex[$subId] = count($nodes);
            $parentRoot = $rootCategories[$sub->parent_id ?? $sub->id] ?? null;
            $nodes[] = [
                'name' => CategoryDisplayName::forCategory($sub),
                'category' => 'outcome',
                'color' => $sub->color ?? $parentRoot?->color,
                'kind' => 'category',
                'depth' => 2,
            ];
        }

        $totalExpense = array_sum($rootTotals);
        if ($totalExpense <= 0) {
            return ['nodes' => [], 'links' => []];
        }

        foreach ($incomeTotals as $incomeId => $incomeAmount) {
            if ($incomeAmount <= 0 || ! isset($incomeIndex[$incomeId])) {
                continue;
            }

            foreach ($rootTotals as $rootId => $rootAmount) {
                if ($rootAmount <= 0 || ! isset($rootIndex[$rootId])) {
                    continue;
                }

                $value = round($incomeAmount * ($rootAmount / $totalExpense), 2);
                if ($value <= 0) {
                    continue;
                }

                $links[] = [
                    'source' => $incomeIndex[$incomeId],
                    'target' => $rootIndex[$rootId],
                    'value' => $value,
                ];
            }
        }

        foreach ($rootToSubTotals as $key => $value) {
            [$rootId, $subId] = array_map(intval(...), explode(':', $key, 2));

            if ($value <= 0 || ! isset($rootIndex[$rootId], $subIndex[$subId])) {
                continue;
            }

            $links[] = [
                'source' => $rootIndex[$rootId],
                'target' => $subIndex[$subId],
                'value' => round($value, 2),
            ];
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
        ];
    }

    /**
     * @param  array<string, mixed>  $filterParams
     *
     * @return Collection<int, object{category_id: int|null, total: mixed}>
     */
    private function aggregateCategoryRows(User $user, array $filterParams, string $operator): Collection
    {
        $query = Transaction::query()
            ->where('transactions.user_id', $user->id)
            ->where('transactions.amount', $operator, 0);

        $this->filters->apply($query, $user, $filterParams);

        /** @var Collection<int, object{category_id: int|null, total: mixed}> */
        return $query
            ->select([
                'transactions.category_id',
                DB::raw('SUM(transactions.amount) as total'),
            ])
            ->groupBy('transactions.category_id')
            ->havingRaw($operator === '>' ? 'SUM(transactions.amount) > 0' : 'SUM(transactions.amount) < 0')
            ->toBase()
            ->get();
    }

    /**
     * @param  Collection<int, Category>  $categoriesById
     */
    private function resolveIncomeSourceCategory(?int $categoryId, Collection $categoriesById): ?Category
    {
        $pair = $this->resolveRootAndSubCategory($categoryId, $categoriesById);

        if ($pair === null) {
            return null;
        }

        [$root, $sub] = $pair;

        if ($root->slug !== self::INCOME_ROOT_SLUG) {
            return null;
        }

        return $sub;
    }

    private function isExcludedExpenseRoot(Category $root): bool
    {
        return in_array($root->slug, [self::INCOME_ROOT_SLUG, self::TRANSFER_ROOT_SLUG], true);
    }

    /**
     * @param  Collection<int, Category>  $categoriesById
     *
     * @return array{0: Category, 1: Category}|null
     */
    private function resolveRootAndSubCategory(?int $categoryId, Collection $categoriesById): ?array
    {
        $leaf = $categoryId !== null
            ? $categoriesById->get($categoryId)
            : $categoriesById->first(
                static fn (Category $category): bool => $category->slug === Category::SLUG_UNCATEGORIZED,
            );

        if ($leaf === null) {
            return null;
        }

        $root = $leaf;
        while ($root->parent_id !== null) {
            $parent = $categoriesById->get($root->parent_id);
            if ($parent === null) {
                break;
            }

            $root = $parent;
        }

        if ($leaf->id === $root->id) {
            return [$root, $root];
        }

        $sub = $leaf;
        while ($sub->parent_id !== null && $sub->parent_id !== $root->id) {
            $parent = $categoriesById->get($sub->parent_id);
            if ($parent === null) {
                break;
            }

            $sub = $parent;
        }

        if ($sub->parent_id !== $root->id && $sub->id !== $root->id) {
            return null;
        }

        return [$root, $sub];
    }
}
