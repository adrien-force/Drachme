<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $transaction = $this->route('transaction');

        return $transaction !== null
            && ($this->user()?->can('update', $transaction) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ];
    }

    public function categoryId(): ?int
    {
        if (! $this->has('category_id')) {
            return null;
        }

        $value = $this->input('category_id');

        return $value === null || $value === '' ? null : (int) $value;
    }
}
