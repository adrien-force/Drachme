<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;

class TransactionCategoryRuleApplier
{
    public function __construct(
        private readonly CategoryMatcher $matcher,
        private readonly RecurringPatternService $recurringPatterns,
    ) {}

    /**
     * @return array{matched: int, scanned: int}
     */
    public function applyToUncategorized(User $user, ?int $accountId = null): array
    {
        $query = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('category_id');

        if ($accountId !== null) {
            $query->where('account_id', $accountId);
        }

        $matched = 0;
        $scanned = 0;

        $query->orderBy('id')->chunkById(200, function ($transactions) use ($user, &$matched, &$scanned): void {
            foreach ($transactions as $transaction) {
                $scanned++;
                $category = $this->matcher->match($user, $transaction->label, $transaction->amount);

                if ($category === null) {
                    continue;
                }

                $transaction->update(['category_id' => $category->id]);
                $transaction->refresh();
                $this->recurringPatterns->syncCategoryFromTransaction($user, $transaction);
                $matched++;
            }
        });

        return [
            'matched' => $matched,
            'scanned' => $scanned,
        ];
    }

    /**
     * @param  list<int>  $transactionIds
     *
     * @return array{matched: int, scanned: int}
     */
    public function applyToTransactionIds(User $user, array $transactionIds): array
    {
        if ($transactionIds === []) {
            return ['matched' => 0, 'scanned' => 0];
        }

        $matched = 0;
        $scanned = 0;

        Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $transactionIds)
            ->whereNull('category_id')
            ->orderBy('id')
            ->chunkById(200, function ($transactions) use ($user, &$matched, &$scanned): void {
                foreach ($transactions as $transaction) {
                    $scanned++;
                    $category = $this->matcher->match($user, $transaction->label, $transaction->amount);

                    if ($category === null) {
                        continue;
                    }

                    $transaction->update(['category_id' => $category->id]);
                    $transaction->refresh();
                    $this->recurringPatterns->syncCategoryFromTransaction($user, $transaction);
                    $matched++;
                }
            });

        return [
            'matched' => $matched,
            'scanned' => $scanned,
        ];
    }

    public function countUncategorized(User $user, ?int $accountId = null): int
    {
        $query = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('category_id');

        if ($accountId !== null) {
            $query->where('account_id', $accountId);
        }

        return $query->count();
    }
}
