<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class TransactionBulkService
{
    public function __construct(
        private readonly TransactionService $transactions,
        private readonly TransactionCategoryRuleApplier $categoryRuleApplier,
    ) {}

    /**
     * @param  list<int>  $transactionIds
     */
    public function updateCategory(User $user, array $transactionIds, ?int $categoryId): int
    {
        $updated = 0;

        foreach ($this->loadOwned($user, $transactionIds) as $transaction) {
            Gate::authorize('update', $transaction);

            try {
                $this->transactions->updateCategory($transaction, $categoryId);
                $updated++;
            } catch (InvalidArgumentException) {
                continue;
            }
        }

        return $updated;
    }

    /**
     * @param  list<int>  $transactionIds
     *
     * @return array{matched: int, scanned: int}
     */
    public function applyCategoryRules(User $user, array $transactionIds): array
    {
        return $this->categoryRuleApplier->applyToTransactionIds($user, $transactionIds);
    }

    /**
     * @param  list<int>  $transactionIds
     *
     * @return array{deleted: int, skipped: int}
     */
    public function delete(User $user, array $transactionIds): array
    {
        $deleted = 0;
        $skipped = 0;

        foreach ($this->loadOwned($user, $transactionIds) as $transaction) {
            if (! Gate::allows('delete', $transaction)) {
                $skipped++;

                continue;
            }

            try {
                $this->transactions->delete($transaction);
                $deleted++;
            } catch (InvalidArgumentException) {
                $skipped++;
            }
        }

        return [
            'deleted' => $deleted,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  list<int>  $transactionIds
     *
     * @return iterable<int, Transaction>
     */
    private function loadOwned(User $user, array $transactionIds): iterable
    {
        if ($transactionIds === []) {
            return;
        }

        yield from Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('id', $transactionIds)
            ->orderBy('id')
            ->cursor();
    }
}
