<?php

declare(strict_types=1);

namespace App\Http\Requests\CategoryRules;

use App\Models\CategoryRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRuleFromLabelRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:500'],
            'selected_tokens' => ['required', 'array', 'min:1'],
            'selected_tokens.*' => ['required', 'string', 'max:255'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'apply_to_transaction_id' => [
                'nullable',
                'integer',
                Rule::exists('transactions', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ];
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
}
