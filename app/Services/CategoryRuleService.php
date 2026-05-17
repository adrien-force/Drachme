<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use App\Support\LabelTokenizer;
use InvalidArgumentException;

class CategoryRuleService
{
    public function __construct(
        private readonly CategoryMatcher $matcher,
    ) {}

    /**
     * @param array{
     *     pattern: string,
     *     category_id: int,
     *     priority?: int,
     *     is_active?: bool,
     * } $data
     */
    public function create(User $user, array $data): CategoryRule
    {
        $pattern = $this->normalizePattern($data['pattern']);
        $category = $this->resolveCategory($user, (int) $data['category_id']);

        return CategoryRule::query()->create([
            'user_id' => $user->id,
            'pattern' => $pattern,
            'category_id' => $category->id,
            'priority' => $data['priority'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * @param  list<string>  $selectedTokens
     */
    public function createFromLabelTokens(
        User $user,
        string $label,
        array $selectedTokens,
        int $categoryId,
        ?int $applyToTransactionId = null,
    ): CategoryRule {
        $available = LabelTokenizer::tokenize($label);
        $valid = [];
        foreach ($selectedTokens as $token) {
            if (in_array($token, $available, true)) {
                $valid[] = $token;
            }
        }

        if ($valid === []) {
            throw new InvalidArgumentException('category_rule_tokens_invalid');
        }

        $rule = $this->create($user, [
            'pattern' => LabelTokenizer::patternFromTokens($valid),
            'category_id' => $categoryId,
        ]);

        if ($applyToTransactionId !== null) {
            $this->applyRuleToTransaction($user, $rule, $applyToTransactionId);
        }

        return $rule;
    }

    /**
     * @param array{
     *     pattern?: string,
     *     category_id?: int,
     *     priority?: int,
     *     is_active?: bool,
     * } $data
     */
    public function update(CategoryRule $rule, array $data): CategoryRule
    {
        $user = User::query()->findOrFail($rule->user_id);

        $updates = [];

        if (array_key_exists('pattern', $data)) {
            $updates['pattern'] = $this->normalizePattern((string) $data['pattern']);
        }

        if (array_key_exists('category_id', $data)) {
            $updates['category_id'] = $this->resolveCategory($user, (int) $data['category_id'])->id;
        }

        if (array_key_exists('priority', $data)) {
            $updates['priority'] = (int) $data['priority'];
        }

        if (array_key_exists('is_active', $data)) {
            $updates['is_active'] = (bool) $data['is_active'];
        }

        $rule->update($updates);

        return $rule->refresh();
    }

    public function delete(CategoryRule $rule): void
    {
        $rule->delete();
    }

    private function applyRuleToTransaction(User $user, CategoryRule $rule, int $transactionId): void
    {
        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->whereKey($transactionId)
            ->first();

        if ($transaction === null) {
            throw new InvalidArgumentException('category_rule_transaction_forbidden');
        }

        $matched = $this->matcher->match($user, $transaction->label);
        if ($matched?->id === $rule->category_id) {
            $transaction->update(['category_id' => $rule->category_id]);
        }
    }

    private function normalizePattern(string $pattern): string
    {
        $normalized = mb_strtolower(trim($pattern));
        if ($normalized === '') {
            throw new InvalidArgumentException('category_rule_pattern_empty');
        }

        return $normalized;
    }

    private function resolveCategory(User $user, int $categoryId): Category
    {
        $category = Category::query()
            ->where('user_id', $user->id)
            ->whereKey($categoryId)
            ->first();

        if ($category === null) {
            throw new InvalidArgumentException('category_rule_category_forbidden');
        }

        return $category;
    }
}
