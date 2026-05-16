<?php

namespace App\Http\Requests\ImportProviders;

use App\Enums\ImportColumnField;
use App\Support\ImportColumnMappingValidator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreImportProviderRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'default_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'column_mapping' => ['required', 'array'],
            'column_mapping.columns' => ['required', 'array', 'min:1'],
            'column_mapping.columns.*.index' => ['required', 'integer', 'min:0'],
            'column_mapping.columns.*.field' => ['required', Rule::enum(ImportColumnField::class)],
            'csv_options' => ['nullable', 'array'],
            'csv_options.delimiter' => ['sometimes', 'string', 'max:1'],
            'csv_options.enclosure' => ['sometimes', 'string', 'max:1'],
            'csv_options.encoding' => ['sometimes', 'string', 'max:32'],
            'csv_options.skip_rows' => ['sometimes', 'integer', 'min:0'],
            'csv_options.date_format' => ['sometimes', 'string', 'max:32'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $mapping = $this->input('column_mapping');

            if (is_array($mapping)) {
                ImportColumnMappingValidator::validate($mapping, $validator);
            }
        });
    }
}
