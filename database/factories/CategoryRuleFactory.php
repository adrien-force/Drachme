<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryRule>
 */
class CategoryRuleFactory extends Factory
{
    protected $model = CategoryRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'pattern' => mb_strtolower(fake()->unique()->word()),
            'priority' => 0,
            'is_active' => true,
        ];
    }
}
