<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ImportColumnField;
use App\Models\Account;
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
        $service = app(ImportProviderService::class);

        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' CSV',
            'default_account_id' => null,
            'column_mapping' => [
                'columns' => [
                    ['index' => 0, 'field' => ImportColumnField::Date->value],
                    ['index' => 1, 'field' => ImportColumnField::Label->value],
                    ['index' => 2, 'field' => ImportColumnField::AmountSigned->value],
                ],
            ],
            'csv_options' => $service->defaultCsvOptions(),
        ];
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $account->user_id,
            'default_account_id' => $account->id,
        ]);
    }
}
