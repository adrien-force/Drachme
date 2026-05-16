<?php

declare(strict_types=1);


namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $balance = fake()->randomFloat(2, 0, 50_000);

        return [
            'user_id' => User::factory(),
            'name' => fake()->company().' '.fake()->randomElement(['Courant', 'Épargne', 'PEA']),
            'institution' => fake()->optional()->company(),
            'type' => fake()->randomElement(AccountType::cases()),
            'initial_balance' => $balance,
            'current_balance' => $balance,
            'currency' => 'EUR',
            'opened_at' => fake()->optional()->date(),
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_archived' => true,
        ]);
    }
}
