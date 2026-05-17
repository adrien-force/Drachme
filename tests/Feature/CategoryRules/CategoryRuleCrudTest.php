<?php

declare(strict_types=1);

namespace Tests\Feature\CategoryRules;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryRuleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_rule_and_match_via_api(): void
    {
        $user = User::factory()->create();
        app(CategoryService::class)->seedDefaultsForUser($user);

        $category = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'groceries')
            ->firstOrFail();

        $this
            ->actingAs($user)
            ->post(route('category-rules.store'), [
                'pattern' => 'leclerc',
                'category_id' => $category->id,
                'priority' => 5,
                'is_active' => true,
            ])
            ->assertRedirect(route('category-rules.index'));

        $this->assertDatabaseHas('category_rules', [
            'user_id' => $user->id,
            'pattern' => 'leclerc',
            'category_id' => $category->id,
        ]);

        $this
            ->actingAs($user)
            ->postJson(route('category-rules.test-match'), [
                'label' => 'ACHAT LECLERC DRIVE',
            ])
            ->assertOk()
            ->assertJsonPath('category.id', $category->id);
    }

    public function test_user_can_create_rule_from_label_tokens(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create(['name' => 'Courses']);

        $this
            ->actingAs($user)
            ->post(route('category-rules.store-from-label'), [
                'label' => 'CB CARREFOUR PARIS 12',
                'selected_tokens' => ['CARREFOUR', 'PARIS'],
                'category_id' => $category->id,
            ])
            ->assertRedirect(route('category-rules.index'));

        $rule = CategoryRule::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($rule);
        $this->assertSame('carrefour paris', $rule->pattern);
    }

    public function test_rule_from_label_can_apply_to_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->create([
            'label' => 'NETFLIX SUBSCRIPTION',
            'category_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->from(route('transactions.index', ['edit_transaction' => $transaction->id]))
            ->post(route('category-rules.store-from-label'), [
                'label' => $transaction->label,
                'selected_tokens' => ['NETFLIX'],
                'category_id' => $category->id,
                'apply_to_transaction_id' => $transaction->id,
            ])
            ->assertRedirect(route('transactions.index', ['edit_transaction' => $transaction->id]));

        $this
            ->actingAs($user)
            ->from(route('accounts.show', [
                'account' => $transaction->account_id,
                'edit_transaction' => $transaction->id,
            ]))
            ->post(route('category-rules.store-from-label'), [
                'label' => $transaction->label,
                'selected_tokens' => ['SPOTIFY'],
                'category_id' => $category->id,
                'apply_to_transaction_id' => $transaction->id,
            ])
            ->assertRedirect(route('accounts.show', [
                'account' => $transaction->account_id,
                'edit_transaction' => $transaction->id,
            ]));

        $transaction->refresh();
        $this->assertSame($category->id, $transaction->category_id);
    }
}
