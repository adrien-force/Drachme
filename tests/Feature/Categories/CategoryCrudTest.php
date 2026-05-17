<?php

declare(strict_types=1);

namespace Tests\Feature\Categories;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_seeds_default_categories(): void
    {
        $user = User::factory()->create();

        app(CategoryService::class)->seedDefaultsForUser($user);

        $this->assertGreaterThanOrEqual(100, Category::query()->where('user_id', $user->id)->count());
        $this->assertNotNull(
            Category::query()
                ->where('user_id', $user->id)
                ->where('slug', Category::SLUG_UNCATEGORIZED)
                ->first(),
        );
        $this->assertNotNull(
            Category::query()
                ->where('user_id', $user->id)
                ->where('slug', 'personal_and_home_essentials')
                ->first(),
        );
        $this->assertNotNull(
            Category::query()
                ->where('user_id', $user->id)
                ->where('slug', 'groceries')
                ->whereNotNull('parent_id')
                ->first(),
        );
    }

    public function test_user_can_create_subcategory(): void
    {
        $user = User::factory()->create();
        app(CategoryService::class)->seedDefaultsForUser($user);

        $parent = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'food_and_beverage')
            ->first();
        $this->assertNotNull($parent);

        $this
            ->actingAs($user)
            ->post(route('categories.store'), [
                'name' => 'Marché bio',
                'parent_id' => $parent->id,
                'color' => '#22c55e',
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'parent_id' => $parent->id,
            'name' => 'Marché bio',
        ]);
    }

    public function test_cannot_delete_uncategorized_category(): void
    {
        $user = User::factory()->create();
        app(CategoryService::class)->seedDefaultsForUser($user);

        $uncategorized = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', Category::SLUG_UNCATEGORIZED)
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->delete(route('categories.destroy', $uncategorized))
            ->assertForbidden();
    }

    public function test_delete_with_transactions_requires_merge(): void
    {
        $user = User::factory()->create();
        $account = \App\Models\Account::factory()->for($user)->create();
        app(CategoryService::class)->seedDefaultsForUser($user);

        $source = Category::factory()->for($user)->create(['name' => 'À supprimer']);
        $target = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', Category::SLUG_UNCATEGORIZED)
            ->firstOrFail();

        Transaction::factory()->for($user)->for($account)->create([
            'category_id' => $source->id,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('categories.destroy', $source))
            ->assertSessionHasErrors('category');

        $this
            ->actingAs($user)
            ->delete(route('categories.destroy', $source), [
                'merge_into_category_id' => $target->id,
            ])
            ->assertRedirect(route('categories.index'));

        $this->assertNull($source->fresh());
        $this->assertSame(1, Transaction::query()->where('category_id', $target->id)->count());
    }

    public function test_max_depth_validation(): void
    {
        $user = User::factory()->create();

        $level1 = Category::factory()->for($user)->create();
        $level2 = Category::factory()->for($user)->create(['parent_id' => $level1->id]);
        $level3 = Category::factory()->for($user)->create(['parent_id' => $level2->id]);

        $this
            ->actingAs($user)
            ->post(route('categories.store'), [
                'name' => 'Trop profond',
                'parent_id' => $level3->id,
            ])
            ->assertSessionHasErrors('parent_id');
    }
}
