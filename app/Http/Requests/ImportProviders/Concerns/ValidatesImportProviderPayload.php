<?php

declare(strict_types=1);

namespace App\Http\Requests\ImportProviders\Concerns;

use App\Enums\ImportColumnField;
use App\Enums\ImportPositionColumnField;
use App\Enums\ImportProviderType;
use App\Support\ImportColumnMappingValidator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait ValidatesImportProviderPayload
{
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mapping = $this->input('column_mapping');

            if (! is_array($mapping)) {
                return;
            }

            $importType = ImportProviderType::tryFrom((string) $this->input('import_type', ''));

            if ($importType === null) {
                return;
            }

            ImportColumnMappingValidator::validateForType($mapping, $importType, $validator);
        });
    }
    /**
     * @return array<string, mixed>
     */
    protected function importProviderMappingRules(): array
    {
        $importType = ImportProviderType::tryFrom((string) $this->input('import_type', ''))
            ?? ImportProviderType::Transactions;

        $fieldRule = $importType === ImportProviderType::Positions
            ? Rule::enum(ImportPositionColumnField::class)
            : Rule::enum(ImportColumnField::class);

        return [
            'import_type' => ['required', Rule::enum(ImportProviderType::class)],
            'column_mapping' => ['required', 'array'],
            'column_mapping.columns' => ['required', 'array', 'min:1'],
            'column_mapping.columns.*.index' => ['required', 'integer', 'min:0'],
            'column_mapping.columns.*.field' => ['required', $fieldRule],
            'csv_options' => ['nullable', 'array'],
            'csv_options.delimiter' => ['sometimes', 'string', 'max:1'],
            'csv_options.enclosure' => ['sometimes', 'string', 'max:1'],
            'csv_options.encoding' => ['sometimes', 'string', 'max:32'],
            'csv_options.skip_rows' => ['sometimes', 'integer', 'min:0'],
            'csv_options.date_format' => ['sometimes', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function importProviderPayloadRules(): array
    {
        $userId = $this->user()?->id;

        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'default_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'account_ids' => ['nullable', 'array'],
            'account_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ], $this->importProviderMappingRules());
    }
}
