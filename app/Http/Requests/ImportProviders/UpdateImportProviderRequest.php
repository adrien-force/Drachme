<?php

declare(strict_types=1);

namespace App\Http\Requests\ImportProviders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImportProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('importProvider')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'default_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'column_mapping' => ['required', 'array'],
            'column_mapping.columns' => ['required', 'array', 'min:1'],
            'csv_options' => ['nullable', 'array'],
        ];
    }
}
