<?php

declare(strict_types=1);

namespace App\Services\TransactionList;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;

final class TransactionListLabelSortApplier
{
    /** Beyond this size, label sort falls back to date (encrypted labels are not SQL-sortable). */
    private const int MAX_IN_MEMORY_SORT = 5000;

    /**
     * @param  Builder<Transaction>  $query
     */
    public function apply(Builder $query, string $order): void
    {
        $direction = $order === 'asc' ? 'asc' : 'desc';

        $ids = (clone $query)
            ->select($query->getModel()->getQualifiedKeyName())
            ->limit(self::MAX_IN_MEMORY_SORT + 1)
            ->pluck($query->getModel()->getKeyName());

        if ($ids->count() > self::MAX_IN_MEMORY_SORT) {
            $query->orderBy('date', $direction)->orderBy('id', $direction);

            return;
        }

        if ($ids->isEmpty()) {
            $query->orderBy('id', $direction);

            return;
        }

        $table = $query->getModel()->getTable();
        $keyName = $query->getModel()->getKeyName();

        $sorted = Transaction::query()
            ->whereIn($keyName, $ids)
            ->get()
            ->sortBy(
                static fn (Transaction $transaction): string => mb_strtolower($transaction->label),
                SORT_NATURAL,
                $direction === 'desc',
            )
            ->pluck($keyName)
            ->values()
            ->all();

        if ($sorted === []) {
            $query->orderBy('id', $direction);

            return;
        }

        $cases = [];
        foreach ($sorted as $position => $id) {
            $cases[] = 'WHEN '.((int) $id).' THEN '.($position + 1);
        }

        $query->orderByRaw(
            'CASE '.$table.'.'.$keyName.' '.implode(' ', $cases).' ELSE '.(count($sorted) + 1).' END',
        );
    }
}
