<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, -500, 500);

        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'date' => fake()->dateTimeBetween('-1 year', 'now'),
            'label' => fake()->sentence(3),
            'amount' => $amount,
            'type' => $amount < 0 ? TransactionType::Expense : TransactionType::Income,
            'import_batch_id' => null,
            'import_hash' => null,
            'notes' => null,
        ];
    }
}
