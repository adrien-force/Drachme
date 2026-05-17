<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_applies_matching_rule_when_no_category_selected(): void
    {
        $user = User::factory()->create();
        app(CategoryService::class)->seedDefaultsForUser($user);
        $account = Account::factory()->for($user)->create();

        $category = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'groceries')
            ->firstOrFail();

        CategoryRule::factory()->for($user)->for($category)->create([
            'pattern' => 'monoprix',
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'account_id' => $account->id,
                'date' => '2024-05-01',
                'label' => 'CB MONOPRIX',
                'amount' => '-18.90',
                'apply_category_rules' => true,
            ])
            ->assertRedirect();

        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertSame($category->id, $transaction->category_id);
    }

    public function test_create_without_rules_leaves_category_null(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'account_id' => $account->id,
                'date' => '2024-05-01',
                'label' => 'Random payment',
                'amount' => '-10.00',
            ])
            ->assertRedirect();

        $this->assertNull(Transaction::query()->value('category_id'));
    }

    public function test_account_show_filters_by_category(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Tagged',
            'category_id' => $category->id,
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Untagged',
            'category_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->get(route('accounts.show', [
                'account' => $account,
                'category_id' => (string) $category->id,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('accounts/accounts-show')
                ->has('transactions.data', 1)
                ->where('transactions.data.0.label', 'Tagged'));
    }
}
