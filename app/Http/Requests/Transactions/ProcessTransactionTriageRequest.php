<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Enums\CategoryRuleFlow;
use App\Enums\RecurringFrequency;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessTransactionTriageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction instanceof Transaction
            && $this->user()?->can('update', $transaction) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'action' => ['required', 'string', Rule::in(['categorize', 'skip'])],
            'category_id' => [
                'required_if:action,categorize',
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('user_id', $userId),
                ),
            ],
            'create_rule' => ['nullable', 'boolean'],
            'selected_tokens' => ['nullable', 'array'],
            'selected_tokens.*' => ['string', 'max:255'],
            'recurring_frequency' => ['nullable', Rule::enum(RecurringFrequency::class)],
            'flow' => ['nullable', Rule::in(['credit', 'debit'])],
            'skip_ids' => ['nullable', 'array'],
            'skip_ids.*' => ['integer'],
        ];
    }

    public function action(): string
    {
        return (string) $this->input('action');
    }

    public function categoryId(): ?int
    {
        $value = $this->input('category_id');

        return is_numeric($value) ? (int) $value : null;
    }

    public function createRule(): bool
    {
        return filter_var($this->input('create_rule'), FILTER_VALIDATE_BOOL) === true;
    }

    /**
     * @return list<string>
     */
    public function selectedTokens(): array
    {
        /** @var list<string> $tokens */
        $tokens = $this->input('selected_tokens', []);

        return $tokens;
    }

    public function ruleFlow(): ?CategoryRuleFlow
    {
        $value = $this->input('flow');

        return is_string($value) && $value !== ''
            ? CategoryRuleFlow::from($value)
            : null;
    }

    public function recurringFrequency(): ?RecurringFrequency
    {
        $value = $this->input('recurring_frequency');

        if (! is_string($value) || $value === '') {
            return null;
        }

        return RecurringFrequency::tryFrom($value);
    }

    /**
     * @return list<int>
     */
    public function skipIds(): array
    {
        /** @var list<int|string> $raw */
        $raw = $this->input('skip_ids', []);

        return array_values(array_map(static fn (int|string $id): int => (int) $id, $raw));
    }
}
