<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'parent_id' => null,
            'name' => fake()->unique()->word(),
            'slug' => null,
            'color' => '#94a3b8',
            'icon' => null,
            'sort_order' => 0,
            'is_system' => false,
        ];
    }

    public function uncategorized(): static
    {
        return $this->state(fn (): array => [
            'name' => 'Non catégorisé',
            'slug' => Category::SLUG_UNCATEGORIZED,
            'is_system' => true,
        ]);
    }
}
