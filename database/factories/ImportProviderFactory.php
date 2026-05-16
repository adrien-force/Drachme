<?php

declare(strict_types=1);

namespace Database\Factories;

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
}
