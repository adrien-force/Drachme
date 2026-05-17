<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\User;
use App\Services\CategoryMatcher;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_label_case_insensitively(): void
    {
        $user = User::factory()->create();
        app(CategoryService::class)->seedDefaultsForUser($user);

        $category = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'groceries')
            ->firstOrFail();

        CategoryRule::factory()->for($user)->for($category)->create([
            'pattern' => 'carrefour',
            'priority' => 10,
            'is_active' => true,
        ]);

        $matched = app(CategoryMatcher::class)->match($user, 'PAIEMENT CARREFOUR CITY');

        $this->assertNotNull($matched);
        $this->assertSame($category->id, $matched->id);
    }

    public function test_higher_priority_rule_wins(): void
    {
        $user = User::factory()->create();
        $lowPriorityCategory = Category::factory()->for($user)->create(['name' => 'Low']);
        $highPriorityCategory = Category::factory()->for($user)->create(['name' => 'High']);

        CategoryRule::factory()->for($user)->for($lowPriorityCategory)->create([
            'pattern' => 'deliveroo',
            'priority' => 1,
        ]);

        CategoryRule::factory()->for($user)->for($highPriorityCategory)->create([
            'pattern' => 'deliveroo',
            'priority' => 50,
        ]);

        $matched = app(CategoryMatcher::class)->match($user, 'DELIVEROO ORDER');

        $this->assertSame($highPriorityCategory->id, $matched?->id);
    }

    public function test_inactive_rules_are_ignored(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();

        CategoryRule::factory()->for($user)->for($category)->create([
            'pattern' => 'netflix',
            'is_active' => false,
        ]);

        $this->assertNull(app(CategoryMatcher::class)->match($user, 'NETFLIX'));
    }
}
