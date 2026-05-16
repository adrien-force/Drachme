<?php

declare(strict_types=1);


namespace Tests\Unit\Services;

use App\Enums\ImportColumnField;
use App\Models\ImportProvider;
use App\Models\User;
use App\Services\ImportProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ImportProviderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_row_with_signed_amount(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::AmountSigned->value],
                ],
            ],
            'csv_options' => [
                'delimiter' => ';',
                'enclosure' => '"',
                'encoding' => 'UTF-8',
                'skip_rows' => 0,
                'date_format' => 'd/m/Y',
            ],
        ]);

        $service = app(ImportProviderService::class);

        $row = $service->normalizeRow(
            ['15/01/2024', 'Salaire', '-1 234,56'],
            $provider,
        );

        $this->assertSame('2024-01-15', $row->date->toDateString());
        $this->assertSame('Salaire', $row->label);
        $this->assertSame(-1234.56, $row->amount);
    }

    public function test_normalize_row_with_debit_and_credit_columns(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::Debit->value],
                    ['index' => 3, 'field' => ImportColumnField::Credit->value],
                ],
            ],
        ]);

        $service = app(ImportProviderService::class);

        $row = $service->normalizeRow(
            ['15/01/2024', 'Loyer', '850,00', ''],
            $provider,
        );

        $this->assertSame(-850.0, $row->amount);

        $creditRow = $service->normalizeRow(
            ['16/01/2024', 'Virement', '', '200,00'],
            $provider,
        );

        $this->assertSame(200.0, $creditRow->amount);
    }

    public function test_normalize_row_with_plus_and_minus_in_same_column(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::AmountSigned->value],
                ],
            ],
        ]);

        $service = app(ImportProviderService::class);

        $credit = $service->normalizeRow(
            ['15/01/2024', 'Virement reçu', '+2 500,00'],
            $provider,
        );

        $debit = $service->normalizeRow(
            ['16/01/2024', 'Prélèvement', '-850,00'],
            $provider,
        );

        $this->assertSame(2500.0, $credit->amount);
        $this->assertSame(-850.0, $debit->amount);
    }

    public function test_normalize_row_throws_when_amount_missing(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                ],
            ],
        ]);

        $service = app(ImportProviderService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->normalizeRow(['15/01/2024', 'Sans montant'], $provider);
    }

    public function test_normalize_row_includes_balance_when_mapped(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create([
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::AmountSigned->value],
                    ['index' => 3, 'field' => ImportColumnField::Balance->value],
                ],
            ],
            'csv_options' => [
                'delimiter' => ';',
                'date_format' => 'd/m/Y',
            ],
        ]);

        $service = app(ImportProviderService::class);

        $row = $service->normalizeRow(
            ['22/07/2022', 'Frais', '-3,00', '1 234,56'],
            $provider,
        );

        $this->assertSame(-3.0, $row->amount);
        $this->assertSame(1234.56, $row->balance);
        $this->assertTrue($service->mapsBalanceColumn($provider));
    }
}
