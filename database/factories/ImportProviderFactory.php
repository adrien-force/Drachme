<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ImportProviderType;
use App\Models\ImportProvider;
use App\Models\User;
use App\Services\ImportProviderService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportProvider>
 */
class ImportProviderFactory extends Factory
{
    protected $model = ImportProvider::class;

    public function configure(): static
    {
        return $this->afterCreating(function (ImportProvider $provider): void {
            if ($provider->default_account_id === null) {
                return;
            }

            $provider->accounts()->syncWithoutDetaching([$provider->default_account_id]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'logo_path' => null,
            'default_account_id' => null,
            'import_type' => ImportProviderType::Transactions,
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => 'date'],
                    ['index' => 1, 'field' => 'label'],
                    ['index' => 2, 'field' => 'amount_signed'],
                ],
            ],
            'csv_options' => app(ImportProviderService::class)->defaultCsvOptions(),
        ];
    }

    public function positions(): static
    {
        return $this->state(fn (): array => [
            'import_type' => ImportProviderType::Positions,
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => 'position_label'],
                    ['index' => 1, 'field' => 'isin'],
                    ['index' => 2, 'field' => 'quantity'],
                    ['index' => 3, 'field' => 'average_price'],
                    ['index' => 4, 'field' => 'last_price'],
                ],
            ],
        ]);
    }
}
