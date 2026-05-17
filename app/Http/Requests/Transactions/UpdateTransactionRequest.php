<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction !== null
            && $this->user()?->can('update', $transaction);
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
        ];
    }
}
