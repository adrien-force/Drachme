<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Http\Requests\Transactions\Concerns\ValidatesCardSettlementFields;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    use ValidatesCardSettlementFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Transaction::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'date' => ['required', 'date'],
            'label' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'not_in:0'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'apply_category_rules' => ['nullable', 'boolean'],
            ...$this->cardSettlementFieldRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->withCardSettlementValidator($validator);
    }
}
