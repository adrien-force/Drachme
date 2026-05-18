<?php

declare(strict_types=1);

namespace App\Http\Requests\CategoryRules;

use App\Models\CategoryRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CategoryRule::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'pattern' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'flow' => ['nullable', Rule::in(['credit', 'debit'])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function flow(): ?\App\Enums\CategoryRuleFlow
    {
        $value = $this->input('flow');

        return is_string($value) && $value !== ''
            ? \App\Enums\CategoryRuleFlow::from($value)
            : null;
    }
}
