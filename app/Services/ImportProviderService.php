<?php

namespace App\Services;

use App\Data\NormalizedRow;
use App\Enums\ImportColumnField;
use App\Models\ImportProvider;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class ImportProviderService
{
    /**
     * @return array<string, mixed>
     */
    public function defaultCsvOptions(): array
    {
        return [
            'delimiter' => ';',
            'enclosure' => '"',
            'encoding' => 'UTF-8',
            'skip_rows' => 0,
            'date_format' => 'd/m/Y',
        ];
    }

    /**
     * @param  array{
     *     name: string,
     *     default_account_id?: int|null,
     *     column_mapping: array<string, mixed>,
     *     csv_options?: array<string, mixed>|null,
     * }  $data
     */
    public function create(User $user, array $data): ImportProvider
    {
        return ImportProvider::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'default_account_id' => $data['default_account_id'] ?? null,
            'column_mapping' => $data['column_mapping'],
            'csv_options' => $this->mergeCsvOptions($data['csv_options'] ?? null),
        ]);
    }

    /**
     * @param  array{
     *     name: string,
     *     default_account_id?: int|null,
     *     column_mapping: array<string, mixed>,
     *     csv_options?: array<string, mixed>|null,
     * }  $data
     */
    public function update(ImportProvider $provider, array $data): ImportProvider
    {
        $provider->fill([
            'name' => $data['name'],
            'default_account_id' => $data['default_account_id'] ?? null,
            'column_mapping' => $data['column_mapping'],
            'csv_options' => $this->mergeCsvOptions($data['csv_options'] ?? $provider->csv_options),
        ]);

        $provider->save();

        return $provider;
    }

    public function delete(ImportProvider $provider): void
    {
        $provider->delete();
    }

    /**
     * @param  list<string|null>  $raw
     */
    public function normalizeRow(array $raw, ImportProvider $provider): NormalizedRow
    {
        $fields = $this->extractFields($raw, $provider);

        if (! isset($fields[ImportColumnField::Date->value])) {
            throw new InvalidArgumentException('Missing date column in row.');
        }

        if (! isset($fields[ImportColumnField::Label->value])) {
            throw new InvalidArgumentException('Missing label column in row.');
        }

        $dateFormat = (string) Arr::get($provider->csv_options, 'date_format', 'd/m/Y');
        $date = CarbonImmutable::createFromFormat(
            $dateFormat,
            trim((string) $fields[ImportColumnField::Date->value]),
        );

        if ($date === false) {
            throw new InvalidArgumentException('Invalid date in row.');
        }

        return new NormalizedRow(
            date: $date,
            label: trim((string) $fields[ImportColumnField::Label->value]),
            amount: $this->resolveAmount($fields),
        );
    }

    /**
     * @param  array<string, mixed>|null  $options
     * @return array<string, mixed>
     */
    private function mergeCsvOptions(?array $options): array
    {
        return array_merge($this->defaultCsvOptions(), $options ?? []);
    }

    /**
     * @param  list<string|null>  $raw
     * @return array<string, string>
     */
    private function extractFields(array $raw, ImportProvider $provider): array
    {
        $mapping = $provider->column_mapping;
        $columns = $mapping['columns'] ?? [];

        if (! is_array($columns)) {
            return [];
        }

        $fields = [];

        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            $index = $column['index'] ?? null;
            $field = $column['field'] ?? null;

            if (! is_int($index) && ! is_numeric($index)) {
                continue;
            }

            if (! is_string($field)) {
                continue;
            }

            $fields[$field] = trim((string) ($raw[(int) $index] ?? ''));
        }

        return $fields;
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function resolveAmount(array $fields): float
    {
        if (isset($fields[ImportColumnField::AmountSigned->value])) {
            return $this->parseNumber($fields[ImportColumnField::AmountSigned->value]);
        }

        $debit = isset($fields[ImportColumnField::Debit->value])
            ? abs($this->parseNumber($fields[ImportColumnField::Debit->value]))
            : 0.0;

        $credit = isset($fields[ImportColumnField::Credit->value])
            ? abs($this->parseNumber($fields[ImportColumnField::Credit->value]))
            : 0.0;

        if ($debit === 0.0 && $credit === 0.0) {
            throw new InvalidArgumentException('Missing amount columns in row.');
        }

        return $credit - $debit;
    }

    private function parseNumber(string $value): float
    {
        $normalized = str_replace([' ', "\u{00A0}"], '', trim($value));
        $normalized = str_replace(',', '.', $normalized);

        return (float) $normalized;
    }
}
