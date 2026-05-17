<?php

declare(strict_types=1);

namespace App\Http\Requests\CategoryRules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rule = $this->route('categoryRule');

        return $rule !== null && ($this->user()?->can('update', $rule) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'pattern' => ['sometimes', 'required', 'string', 'max:255'],
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'priority' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
