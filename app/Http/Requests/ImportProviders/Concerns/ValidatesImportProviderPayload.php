<?php

declare(strict_types=1);


namespace App\Http\Requests\ImportProviders\Concerns;

use App\Enums\ImportColumnField;
use App\Http\Requests\Concerns\ValidatesEntityLogo;
use Illuminate\Validation\Rule;

trait ValidatesImportProviderPayload
{
    use ValidatesEntityLogo;
    /**
     * @return array<string, mixed>
     */
    protected function importProviderMappingRules(): array
    {
        return [
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
            ...$this->entityLogoRules(),
        ], $this->importProviderMappingRules());
    }
}
