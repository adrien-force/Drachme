<?php

declare(strict_types=1);

namespace App\Http\Requests\ImportProviders;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PreviewImportProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sample_rows' => ['required', 'array', 'min:1'],
            'sample_rows.*' => ['array'],
            'column_mapping' => ['required', 'array'],
            'column_mapping.columns' => ['required', 'array', 'min:1'],
            'csv_options' => ['nullable', 'array'],
        ];
    }
}
