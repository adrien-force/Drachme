<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'isin' => 'FR'.strtoupper(fake()->bothify('##########')),
            'label' => fake()->company().' ETF',
            'quantity' => fake()->randomFloat(4, 1, 500),
            'average_price' => fake()->randomFloat(4, 10, 500),
            'last_price' => null,
            'last_price_at' => null,
        ];
    }

    public function withLastPrice(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_price' => fake()->randomFloat(4, 10, 500),
            'last_price_at' => now(),
        ]);
    }
}
