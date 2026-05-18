<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryRuleFlow;
use App\Enums\AccountType;
use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use App\Support\LabelTokenizer;
use InvalidArgumentException;

class TransactionTriageService
{
    public function __construct(
        private readonly CategoryMatcher $matcher,
        private readonly CategoryRuleService $rules,
        private readonly TransactionService $transactions,
        private readonly TransactionCategoryRuleApplier $ruleApplier,
        private readonly RecurringPatternService $recurringPatterns,
        private readonly AccountService $accounts,
    ) {}

    public function countUncategorized(User $user): int
    {
        return $this->ruleApplier->countUncategorized($user);
    }

    /**
     * @param  list<int>  $skipIds
     */
    public function nextUncategorized(User $user, array $skipIds = []): ?Transaction
    {
        $query = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('category_id')
            ->with(['account:id,name,logo_path,type', 'category:id,name,color'])
            ->orderBy('date')
            ->orderBy('id');

        if ($skipIds !== []) {
            $query->whereNotIn('id', $skipIds);
        }

        return $query->first();
    }

    /**
     * @return array{matched: int, scanned: int}
     */
    public function applyAllRules(User $user): array
    {
        return $this->ruleApplier->applyToUncategorized($user);
    }

    /**
     * @param  list<string>  $selectedTokens
     * @return array{auto_matched: int, auto_scanned: int}
     */
    public function categorize(
        User $user,
        Transaction $transaction,
        int $categoryId,
        bool $createRule,
        array $selectedTokens,
        ?RecurringFrequency $recurringFrequency,
        ?CategoryRuleFlow $ruleFlow = null,
    ): array {
        if ($transaction->user_id !== $user->id) {
            throw new InvalidArgumentException('transaction_invalid');
        }

        $this->transactions->updateCategory($transaction, $categoryId);
        $transaction->refresh();

        if ($recurringFrequency !== null) {
            $this->recurringPatterns->confirmFromTransaction($user, $transaction, $recurringFrequency);
        }

        if (! $createRule || $selectedTokens === []) {
            return ['auto_matched' => 0, 'auto_scanned' => 0];
        }

        $this->rules->createFromLabelTokens(
            $user,
            $transaction->label,
            $selectedTokens,
            $categoryId,
            $transaction->id,
            $ruleFlow,
        );

        $result = $this->ruleApplier->applyToUncategorized($user);

        return [
            'auto_matched' => $result['matched'],
            'auto_scanned' => $result['scanned'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function serializeTransaction(User $user, ?Transaction $transaction): ?array
    {
        if ($transaction === null) {
            return null;
        }

        $suggested = $this->matcher->match($user, $transaction->label, $transaction->amount);
        $account = $transaction->account;
        $type = $transaction->type;
        $date = $transaction->date;

        return [
            'id' => $transaction->id,
            'date' => $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : (string) $date,
            'label' => $transaction->label,
            'amount' => (float) $transaction->amount,
            'type' => $type instanceof TransactionType ? $type->value : (string) $type,
            'account_id' => $transaction->account_id,
            'account_name' => $account?->name,
            'account_logo_url' => $account !== null ? $this->accounts->logoUrl($account) : null,
            'account_type' => $account !== null
                ? ($account->type instanceof AccountType ? $account->type->value : (string) $account->type)
                : null,
            'label_tokens' => LabelTokenizer::tokenize($transaction->label),
            'suggested_category_id' => $suggested?->id,
            'suggested_category_name' => $suggested?->name,
            'suggested_category_color' => $suggested?->color,
            'is_card_settlement' => (bool) $transaction->is_card_settlement,
        ];
    }
}
