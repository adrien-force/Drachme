<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use App\Enums\AccountType;
use App\Http\Requests\Concerns\ValidatesEntityLogo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountRequest extends FormRequest
{
    use ValidatesEntityLogo;
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('account')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'opened_at' => ['nullable', 'date'],
            ...$this->entityLogoRules(),
        ];
    }
}
