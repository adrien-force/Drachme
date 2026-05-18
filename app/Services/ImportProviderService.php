<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\NormalizedRow;
use App\Enums\ImportColumnField;
use App\Enums\ImportPositionColumnField;
use App\Enums\ImportProviderType;
use App\Models\ImportProvider;
use App\Support\Isin;
use App\Support\MarketSymbol;
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
            'skip_rows' => 1,
            'date_format' => 'd/m/Y',
        ];
    }

    /**
     * @param  array{
     *     name: string,
     *     default_account_id?: int|null,
     *     account_ids?: list<int>|null,
     *     import_type?: string|ImportProviderType,
     *     column_mapping: array<string, mixed>,
     *     csv_options?: array<string, mixed>|null,
     * }  $data
     */
    public function create(User $user, array $data): ImportProvider
    {
        $provider = ImportProvider::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'default_account_id' => $data['default_account_id'] ?? null,
            'import_type' => $this->resolveImportType($data['import_type'] ?? null),
            'column_mapping' => $data['column_mapping'],
            'csv_options' => $this->mergeCsvOptions($data['csv_options'] ?? null),
        ]);

        $this->syncLinkedAccounts(
            $provider,
            $data['account_ids'] ?? null,
            $data['default_account_id'] ?? null,
        );

        return $provider;
    }

    /**
     * @param  array{
     *     name: string,
     *     default_account_id?: int|null,
     *     account_ids?: list<int>|null,
     *     import_type?: string|ImportProviderType,
     *     column_mapping: array<string, mixed>,
     *     csv_options?: array<string, mixed>|null,
     * }  $data
     */
    public function update(ImportProvider $provider, array $data): ImportProvider
    {
        $provider->fill([
            'name' => $data['name'],
            'default_account_id' => $data['default_account_id'] ?? null,
            'import_type' => $this->resolveImportType($data['import_type'] ?? $provider->import_type),
            'column_mapping' => $data['column_mapping'],
            'csv_options' => $this->mergeCsvOptions($data['csv_options'] ?? $this->resolvedCsvOptions($provider)),
        ]);

        $provider->save();

        if (array_key_exists('account_ids', $data)) {
            $this->syncLinkedAccounts(
                $provider,
                $data['account_ids'],
                $data['default_account_id'] ?? null,
            );
        } elseif (($data['default_account_id'] ?? null) !== null) {
            $provider->accounts()->syncWithoutDetaching([$data['default_account_id']]);
        }

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
     * @param  list<list<string|null>>  $sampleRows
     * @param  array<string, mixed>  $columnMapping
     * @param  array<string, mixed>  $csvOptions
     * @return list<array<string, mixed>>
     */
    public function previewPositionRows(array $sampleRows, array $columnMapping, array $csvOptions): array
    {
        $provider = new ImportProvider([
            'import_type' => ImportProviderType::Positions,
            'column_mapping' => $columnMapping,
            'csv_options' => $this->mergeCsvOptions($csvOptions),
        ]);

        $preview = [];
        $skipRows = (int) Arr::get($this->mergeCsvOptions($csvOptions), 'skip_rows', 0);

        foreach (array_slice($sampleRows, 0, 10) as $index => $raw) {
            $lineNumber = $skipRows + $index + 1;

            try {
                $normalized = $this->normalizePositionRow($raw, $provider);
                $unitPrice = $normalized['last_price'] ?? $normalized['average_price'];
                $quantity = (float) $normalized['quantity'];

                $preview[] = [
                    'line' => $lineNumber,
                    'label' => $normalized['label'],
                    'isin' => $normalized['isin'],
                    'quantity' => $quantity,
                    'average_price' => (float) $normalized['average_price'],
                    'last_price' => $normalized['last_price'] !== null
                        ? (float) $normalized['last_price']
                        : null,
                    'market_value' => $quantity * (float) $unitPrice,
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
     * @return array{
     *     label: string,
     *     isin: string,
     *     quantity: string,
     *     average_price: string,
     *     last_price: string|null,
     * }
     */
    public function normalizePositionRow(array $raw, ImportProvider $provider): array
    {
        $fields = $this->extractFields($raw, $provider);

        if (! array_key_exists(ImportPositionColumnField::Isin->value, $fields)) {
            throw new InvalidArgumentException(
                ImportRowError::positionFieldNotMapped(ImportPositionColumnField::Isin),
            );
        }

        if (! array_key_exists(ImportPositionColumnField::Quantity->value, $fields)) {
            throw new InvalidArgumentException(
                ImportRowError::positionFieldNotMapped(ImportPositionColumnField::Quantity),
            );
        }

        $isinRaw = trim($fields[ImportPositionColumnField::Isin->value]);

        if ($isinRaw === '') {
            throw new InvalidArgumentException(ImportRowError::positionIsinEmpty());
        }

        if (! Isin::isValid($isinRaw)) {
            throw new InvalidArgumentException(ImportRowError::positionIsinInvalid($isinRaw));
        }

        $quantityRaw = trim($fields[ImportPositionColumnField::Quantity->value]);

        if ($quantityRaw === '' || ! $this->looksLikeAmount($quantityRaw)) {
            throw new InvalidArgumentException(ImportRowError::positionQuantityInvalid($quantityRaw));
        }

        $quantity = $this->parseUnsignedNumber($quantityRaw);

        if ($quantity <= 0) {
            throw new InvalidArgumentException(ImportRowError::positionQuantityInvalid($quantityRaw));
        }

        $averagePrice = $this->resolvePositionPrice(
            $fields,
            ImportPositionColumnField::AveragePrice,
        );
        $lastPrice = $this->resolveOptionalPositionPrice(
            $fields,
            ImportPositionColumnField::LastPrice,
        );

        if ($averagePrice === null && $lastPrice === null) {
            throw new InvalidArgumentException(ImportRowError::positionPriceMissing());
        }

        $label = array_key_exists(ImportPositionColumnField::PositionLabel->value, $fields)
            ? trim($fields[ImportPositionColumnField::PositionLabel->value])
            : '';

        if ($label === '') {
            $label = Isin::normalize($isinRaw);
        }

        $marketSymbol = null;

        if (array_key_exists(ImportPositionColumnField::MarketSymbol->value, $fields)) {
            $symbolRaw = trim($fields[ImportPositionColumnField::MarketSymbol->value]);

            if ($symbolRaw !== '') {
                $marketSymbol = MarketSymbol::normalize($symbolRaw);

                if ($marketSymbol === null) {
                    throw new InvalidArgumentException(
                        ImportRowError::positionMarketSymbolInvalid($symbolRaw),
                    );
                }
            }
        }

        return [
            'label' => $label,
            'isin' => Isin::normalize($isinRaw),
            'market_symbol' => $marketSymbol,
            'quantity' => number_format($quantity, 6, '.', ''),
            'average_price' => number_format($averagePrice ?? $lastPrice ?? 0.0, 6, '.', ''),
            'last_price' => $lastPrice !== null
                ? number_format($lastPrice, 6, '.', '')
                : null,
        ];
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

    /**
     * @param  array<string, string>  $fields
     */
    private function resolvePositionPrice(array $fields, ImportPositionColumnField $field): ?float
    {
        if (! array_key_exists($field->value, $fields)) {
            return null;
        }

        $raw = trim($fields[$field->value]);

        if ($raw === '' || ! $this->looksLikeAmount($raw)) {
            return null;
        }

        return $this->parseUnsignedNumber($raw);
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function resolveOptionalPositionPrice(array $fields, ImportPositionColumnField $field): ?float
    {
        return $this->resolvePositionPrice($fields, $field);
    }

    /**
     * @param  list<int>|null  $accountIds
     */
    private function syncLinkedAccounts(
        ImportProvider $provider,
        ?array $accountIds,
        ?int $defaultAccountId,
    ): void {
        if ($accountIds === null && $defaultAccountId === null) {
            return;
        }

        $ids = collect($accountIds ?? [])
            ->map(static fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        if ($defaultAccountId !== null && ! $ids->contains($defaultAccountId)) {
            $ids->push($defaultAccountId);
        }

        $provider->accounts()->sync($ids->all());
    }

    private function resolveImportType(mixed $importType): ImportProviderType
    {
        if ($importType instanceof ImportProviderType) {
            return $importType;
        }

        if (is_string($importType)) {
            $resolved = ImportProviderType::tryFrom($importType);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return ImportProviderType::Transactions;
    }
}
