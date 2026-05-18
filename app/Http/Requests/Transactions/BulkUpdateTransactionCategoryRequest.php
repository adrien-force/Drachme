<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use Illuminate\Validation\Rule;

class BulkUpdateTransactionCategoryRequest extends BulkTransactionIdsRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            ...parent::rules(),
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('user_id', $userId),
                ),
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
