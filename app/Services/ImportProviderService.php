<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\NormalizedRow;
use App\Enums\ImportColumnField;
use App\Models\ImportProvider;
use App\Models\User;
use App\Support\DateFormatDetector;
use App\Support\ImportRowError;
use App\Support\SignedAmountParser;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class ImportProviderService
{
    public function __construct(
        private readonly DateFormatDetector $dateFormats,
        private readonly SignedAmountParser $signedAmounts,
    ) {}
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
            'csv_options' => $this->mergeCsvOptions($data['csv_options'] ?? $this->resolvedCsvOptions($provider)),
        ]);

        $provider->save();

        return $provider;
    }

    public function delete(ImportProvider $provider): void
    {
        $provider->delete();
    }

    /**
     * @param  list<list<string|null>>  $sampleRows
     * @param  array<string, mixed>  $columnMapping
     * @param  array<string, mixed>  $csvOptions
     * @return list<array{date: string, label: string, amount: float}|array{error: string}>
     */
    public function previewNormalizedRows(array $sampleRows, array $columnMapping, array $csvOptions): array
    {
        $provider = new ImportProvider([
            'column_mapping' => $columnMapping,
            'csv_options' => $this->mergeCsvOptions($csvOptions),
        ]);

        $preview = [];
        $skipRows = (int) Arr::get($this->mergeCsvOptions($csvOptions), 'skip_rows', 0);

        foreach (array_slice($sampleRows, 0, 3) as $index => $raw) {
            $lineNumber = $skipRows + $index + 1;

            try {
                $normalized = $this->normalizeRow($raw, $provider);

                $preview[] = [
                    'line' => $lineNumber,
                    'date' => $normalized->date->format('Y-m-d'),
                    'label' => $normalized->label,
                    'amount' => $normalized->amount,
                    'balance' => $normalized->balance,
                ];
            } catch (\Throwable $exception) {
                $preview[] = [
                    'line' => $lineNumber,
                    'error' => ImportRowError::wrapLine($lineNumber, $exception->getMessage()),
                ];
            }
        }

        return $preview;
    }

    /**
     * @param  list<string|null>  $raw
     */
    public function normalizeRow(array $raw, ImportProvider $provider): NormalizedRow
    {
        $fields = $this->extractFields($raw, $provider);

        if (! array_key_exists(ImportColumnField::Date->value, $fields)) {
            throw new InvalidArgumentException(
                ImportRowError::fieldNotMapped(ImportColumnField::Date),
            );
        }

        if (! array_key_exists(ImportColumnField::Label->value, $fields)) {
            throw new InvalidArgumentException(
                ImportRowError::fieldNotMapped(ImportColumnField::Label),
            );
        }

        $dateFormat = (string) Arr::get($this->resolvedCsvOptions($provider), 'date_format', 'd/m/Y');
        $date = $this->dateFormats->parse(
            (string) $fields[ImportColumnField::Date->value],
            $dateFormat,
        );

        $label = trim((string) $fields[ImportColumnField::Label->value]);

        if ($label === '') {
            throw new InvalidArgumentException(ImportRowError::labelEmpty());
        }

        return new NormalizedRow(
            date: $date,
            label: $label,
            amount: $this->resolveAmount($fields),
            balance: $this->resolveBalance($fields),
        );
    }

    public function mapsBalanceColumn(ImportProvider $provider): bool
    {
        foreach ($this->resolvedMappingColumns($provider) as $column) {
            if ($column['field'] === ImportColumnField::Balance->value) {
                return true;
            }
        }

        return false;
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
     * @return array<string, mixed>
     */
    private function resolvedCsvOptions(ImportProvider $provider): array
    {
        return $provider->csv_options;
    }

    /**
     * @return list<array{index: int, field: string}>
     */
    private function resolvedMappingColumns(ImportProvider $provider): array
    {
        $columns = $provider->column_mapping['columns'] ?? null;

        if (! is_array($columns)) {
            return [];
        }

        /** @var list<array{index: int, field: string}> $normalized */
        $normalized = [];

        foreach ($columns as $column) {
            if (! is_array($column)) {
                continue;
            }

            $index = $column['index'] ?? null;
            $field = $column['field'] ?? null;

            if ((! is_int($index) && ! is_numeric($index)) || ! is_string($field)) {
                continue;
            }

            $normalized[] = [
                'index' => (int) $index,
                'field' => $field,
            ];
        }

        return $normalized;
    }

    /**
     * @param  list<string|null>  $raw
     * @return array<string, string>
     */
    private function extractFields(array $raw, ImportProvider $provider): array
    {
        $fields = [];

        foreach ($this->resolvedMappingColumns($provider) as $column) {
            $index = $column['index'];
            $field = $column['field'];

            $fields[$field] = trim((string) ($raw[(int) $index] ?? ''));
        }

        return $fields;
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function resolveAmount(array $fields): float
    {
        if (array_key_exists(ImportColumnField::AmountSigned->value, $fields)) {
            $rawAmount = trim($fields[ImportColumnField::AmountSigned->value]);

            if ($rawAmount === '') {
                throw new InvalidArgumentException(ImportRowError::amountSignedEmpty());
            }

            if (! $this->looksLikeAmount($rawAmount)) {
                throw new InvalidArgumentException(
                    ImportRowError::amountSignedInvalid($rawAmount),
                );
            }

            return $this->signedAmounts->parse($rawAmount);
        }

        $debit = isset($fields[ImportColumnField::Debit->value])
            ? abs($this->parseUnsignedNumber($fields[ImportColumnField::Debit->value]))
            : 0.0;

        $credit = isset($fields[ImportColumnField::Credit->value])
            ? abs($this->parseUnsignedNumber($fields[ImportColumnField::Credit->value]))
            : 0.0;

        if ($debit === 0.0 && $credit === 0.0) {
            throw new InvalidArgumentException(ImportRowError::amountMissing($fields));
        }

        return $credit - $debit;
    }

    private function looksLikeAmount(string $value): bool
    {
        $normalized = str_replace([' ', "\u{00A0}"], '', trim($value));

        return $normalized !== '' && preg_match('/^[-+]?[\d.,()]+$/u', $normalized) === 1;
    }

    private function parseUnsignedNumber(string $value): float
    {
        $normalized = str_replace([' ', "\u{00A0}"], '', trim($value));
        $normalized = str_replace(',', '.', $normalized);

        return abs((float) $normalized);
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function resolveBalance(array $fields): ?float
    {
        if (! array_key_exists(ImportColumnField::Balance->value, $fields)) {
            return null;
        }

        $rawBalance = trim($fields[ImportColumnField::Balance->value]);

        if ($rawBalance === '' || ! $this->looksLikeAmount($rawBalance)) {
            return null;
        }

        return $this->signedAmounts->parse($rawBalance);
    }
}
