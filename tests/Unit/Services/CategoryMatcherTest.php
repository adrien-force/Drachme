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

    public function test_longer_pattern_wins_over_shorter_substring_despite_lower_priority(): void
    {
        $user = User::factory()->create();
        $youtubeCategory = Category::factory()->for($user)->create(['name' => 'YouTube']);
        $subscriptionCategory = Category::factory()->for($user)->create(['name' => 'Abonnements']);

        CategoryRule::factory()->for($user)->for($youtubeCategory)->create([
            'pattern' => 'youtube',
            'priority' => 100,
        ]);

        CategoryRule::factory()->for($user)->for($subscriptionCategory)->create([
            'pattern' => 'abonnement youtube',
            'priority' => 0,
        ]);

        $matched = app(CategoryMatcher::class)->match(
            $user,
            'PRLV ABONNEMENT YOUTUBE PREMIUM',
        );

        $this->assertSame($subscriptionCategory->id, $matched?->id);
    }

    public function test_flow_credit_rule_does_not_match_debit_amount(): void
    {
        $user = User::factory()->create();
        $creditCategory = Category::factory()->for($user)->create(['name' => 'Virement reçu']);
        $debitCategory = Category::factory()->for($user)->create(['name' => 'Paiement']);

        CategoryRule::factory()->for($user)->for($creditCategory)->create([
            'pattern' => 'dupont',
            'flow' => 'credit',
        ]);

        CategoryRule::factory()->for($user)->for($debitCategory)->create([
            'pattern' => 'dupont',
            'flow' => 'debit',
        ]);

        $creditMatch = app(CategoryMatcher::class)->match($user, 'VIR DUPONT', 150.0);
        $debitMatch = app(CategoryMatcher::class)->match($user, 'PRLV DUPONT', -42.5);

        $this->assertSame($creditCategory->id, $creditMatch?->id);
        $this->assertSame($debitCategory->id, $debitMatch?->id);
    }

    public function test_flow_specific_rule_wins_over_generic_same_pattern(): void
    {
        $user = User::factory()->create();
        $generic = Category::factory()->for($user)->create(['name' => 'Générique']);
        $debitOnly = Category::factory()->for($user)->create(['name' => 'Dépense']);

        CategoryRule::factory()->for($user)->for($generic)->create([
            'pattern' => 'dupont',
            'flow' => null,
            'priority' => 100,
        ]);

        CategoryRule::factory()->for($user)->for($debitOnly)->create([
            'pattern' => 'dupont',
            'flow' => 'debit',
            'priority' => 0,
        ]);

        $matched = app(CategoryMatcher::class)->match($user, 'PRLV DUPONT', -10.0);

        $this->assertSame($debitOnly->id, $matched?->id);
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
