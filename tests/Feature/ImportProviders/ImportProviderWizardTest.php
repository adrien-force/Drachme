<?php

declare(strict_types=1);


namespace Tests\Feature\ImportProviders;

use App\Enums\ImportColumnField;
use App\Enums\ImportPositionColumnField;
use App\Models\Account;
use App\Models\ImportProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportProviderWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_page_is_accessible(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->create(['name' => 'Compte courant']);

        $this
            ->actingAs($user)
            ->get(route('providers.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('providers/providers-form')
                ->where('provider', null)
                ->has('accounts', 1)
                ->has('fieldOptions', 7)
                ->has('positionFieldOptions', 6));
    }

    public function test_preview_returns_normalized_rows(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->postJson(route('providers.preview'), [
                'import_type' => 'transactions',
                'sample_rows' => [
                    ['15/01/2024', 'Loyer', '850,00', ''],
                ],
                'column_mapping' => [
                    'columns' => [
                        ['index' => 0, 'field' => ImportColumnField::Date->value],
                        ['index' => 1, 'field' => ImportColumnField::Label->value],
                        ['index' => 2, 'field' => ImportColumnField::Debit->value],
                        ['index' => 3, 'field' => ImportColumnField::Credit->value],
                    ],
                ],
                'csv_options' => [
                    'delimiter' => ';',
                    'date_format' => 'd/m/Y',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('rows.0.date', '2024-01-15')
            ->assertJsonPath('rows.0.label', 'Loyer')
            ->assertJsonPath('rows.0.amount', -850);
    }

    public function test_detect_date_format_endpoint_returns_suggestion(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->postJson(route('providers.detect-date-format'), [
                'samples' => ['15/01/2024', '16/02/2024', '01/03/2024'],
            ])
            ->assertOk()
            ->assertJsonPath('suggestion.format', 'd/m/Y')
            ->assertJsonPath('suggestion.matched', 3);
    }

    public function test_detect_date_format_endpoint_handles_iso_datetime(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->postJson(route('providers.detect-date-format'), [
                'samples' => [
                    '2022-07-22 01:34:51',
                    '2022-07-23 14:05:00',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('suggestion.format', 'Y-m-d H:i:s')
            ->assertJsonPath('suggestion.matched', 2);
    }

    public function test_preview_returns_normalized_position_rows(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->postJson(route('providers.preview'), [
                'import_type' => 'positions',
                'sample_rows' => [
                    [
                        'Apple Inc',
                        'US0378331005',
                        '10',
                        '150,00',
                        '175,50',
                        '',
                        '',
                        '',
                        '',
                    ],
                ],
                'column_mapping' => [
                    'columns' => [
                        ['index' => 0, 'field' => ImportPositionColumnField::PositionLabel->value],
                        ['index' => 1, 'field' => ImportPositionColumnField::Isin->value],
                        ['index' => 2, 'field' => ImportPositionColumnField::Quantity->value],
                        ['index' => 3, 'field' => ImportPositionColumnField::AveragePrice->value],
                        ['index' => 4, 'field' => ImportPositionColumnField::LastPrice->value],
                        ['index' => 5, 'field' => ImportPositionColumnField::Skip->value],
                        ['index' => 6, 'field' => ImportPositionColumnField::Skip->value],
                        ['index' => 7, 'field' => ImportPositionColumnField::Skip->value],
                        ['index' => 8, 'field' => ImportPositionColumnField::Skip->value],
                    ],
                ],
                'csv_options' => [
                    'delimiter' => ';',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('import_type', 'positions')
            ->assertJsonPath('rows.0.isin', 'US0378331005')
            ->assertJsonPath('rows.0.quantity', 10)
            ->assertJsonPath('rows.0.label', 'Apple Inc');
    }

    public function test_edit_page_loads_existing_provider(): void
    {
        $user = User::factory()->create();
        $provider = ImportProvider::factory()->for($user)->create(['name' => 'BNP CSV']);

        $this
            ->actingAs($user)
            ->get(route('providers.edit', $provider))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('providers/providers-form')
                ->where('provider.name', 'BNP CSV'));
    }
}
