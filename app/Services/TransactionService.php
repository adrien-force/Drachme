<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Events\TransactionChanged;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use InvalidArgumentException;

class TransactionService
{
    public function __construct(
        private readonly CategoryMatcher $categoryMatcher,
    ) {}

    /**
     * @param  array{
     *     account_id: int,
     *     date: string,
     *     label: string,
     *     amount: float|string,
     *     type?: string|null,
     *     notes?: string|null,
     *     category_id?: int|null,
     *     apply_category_rules?: bool,
     * }  $data
     */
    public function create(User $user, array $data): Transaction
    {
        $account = $this->resolveAccount($user, (int) $data['account_id']);
        $amount = $this->parseAmount($data['amount']);
        $type = $this->resolveType($amount, isset($data['type']) ? (string) $data['type'] : null);
        $categoryId = $this->resolveCategoryId(
            $user,
            $data['label'],
            $data['category_id'] ?? null,
            ($data['apply_category_rules'] ?? true) === true,
        );

        $transaction = Transaction::query()->create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'date' => $data['date'],
            'label' => $data['label'],
            'amount' => $amount,
            'type' => $type,
            'category_id' => $categoryId,
            'notes' => $data['notes'] ?? null,
        ]);

        TransactionChanged::dispatch($account);

        return $transaction;
    }

    /**
     * @param  array{
     *     account_id: int,
     *     date: string,
     *     label: string,
     *     amount: float|string,
     *     type?: string|null,
     *     notes?: string|null,
     *     category_id?: int|null,
     *     apply_category_rules?: bool,
     * }  $data
     */
    public function update(Transaction $transaction, array $data): Transaction
    {
        $user = $transaction->user;
        if ($user === null) {
            throw new InvalidArgumentException('transaction_invalid');
        }

        $previousAccountId = $transaction->account_id;
        $account = $this->resolveAccount($user, (int) $data['account_id']);
        $amount = $this->parseAmount($data['amount']);
        $type = $this->resolveType($amount, isset($data['type']) ? (string) $data['type'] : null);
        $categoryId = $this->resolveCategoryId(
            $user,
            $data['label'],
            $data['category_id'] ?? null,
            ($data['apply_category_rules'] ?? false) === true,
        );

        $transaction->update([
            'account_id' => $account->id,
            'date' => $data['date'],
            'label' => $data['label'],
            'amount' => $amount,
            'type' => $type,
            'category_id' => $categoryId,
            'notes' => $data['notes'] ?? null,
        ]);

        TransactionChanged::dispatch($account);

        if ($previousAccountId !== $account->id) {
            $previousAccount = Account::query()->find($previousAccountId);
            if ($previousAccount !== null) {
                TransactionChanged::dispatch($previousAccount);
            }
        }

        return $transaction->fresh() ?? $transaction;
    }

    public function updateCategory(Transaction $transaction, ?int $categoryId): Transaction
    {
        $user = $transaction->user;
        if ($user === null) {
            throw new InvalidArgumentException('transaction_invalid');
        }

        if ($categoryId !== null) {
            $category = Category::query()
                ->where('user_id', $user->id)
                ->whereKey($categoryId)
                ->first();

            if ($category === null) {
                throw new InvalidArgumentException('transaction_category_forbidden');
            }
        }

        $transaction->update(['category_id' => $categoryId]);

        return $transaction->fresh(['category:id,name,color']) ?? $transaction;
    }

    public function delete(Transaction $transaction): void
    {
        if ($transaction->transfer_pair_id !== null) {
            throw new InvalidArgumentException('transaction_transfer_linked');
        }

        $accountId = $transaction->account_id;
        $transaction->delete();

        $account = Account::query()->find($accountId);
        if ($account !== null) {
            TransactionChanged::dispatch($account);
        }
    }

    private function resolveAccount(User $user, int $accountId): Account
    {
        $account = Account::query()
            ->whereKey($accountId)
            ->where('user_id', $user->id)
            ->first();

        if ($account === null) {
            throw new InvalidArgumentException('transaction_account_forbidden');
        }

        return $account;
    }

    private function parseAmount(float|string $amount): string
    {
        $value = (float) $amount;

        if ($value === 0.0) {
            throw new InvalidArgumentException('transaction_amount_zero');
        }

        return number_format($value, 2, '.', '');
    }

    private function resolveType(float|string $amount, ?string $type): TransactionType
    {
        if ($type !== null && $type !== '') {
            $resolved = TransactionType::tryFrom($type);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        $value = (float) $amount;

        if ($value < 0) {
            return TransactionType::Expense;
        }

        if ($value > 0) {
            return TransactionType::Income;
        }

        return TransactionType::Transfer;
    }

    private function resolveCategoryId(
        User $user,
        string $label,
        ?int $explicitCategoryId,
        bool $applyRules,
    ): ?int {
        if ($explicitCategoryId !== null) {
            $category = Category::query()
                ->where('user_id', $user->id)
                ->whereKey($explicitCategoryId)
                ->first();

            if ($category === null) {
                throw new InvalidArgumentException('transaction_category_forbidden');
            }

            return $category->id;
        }

        if (! $applyRules) {
            return null;
        }

        return $this->categoryMatcher->match($user, $label)?->id;
    }
}
