<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_index_is_paginated(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->count(60)->create();

        $this
            ->actingAs($user)
            ->get(route('transactions.index', ['per_page' => 50]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 50)
                ->where('transactions.meta.total', 60)
                ->where('transactions.meta.per_page', 50)
                ->where('filters.per_page', 50));
    }

    public function test_search_filters_by_label(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create(['label' => 'Courses Carrefour']);
        Transaction::factory()->for($user)->for($account)->create(['label' => 'Salaire']);

        $this
            ->actingAs($user)
            ->get(route('transactions.index', ['search' => 'Carrefour']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.label', 'Courses Carrefour'));
    }

    public function test_account_filter_limits_results(): void
    {
        $user = User::factory()->create();
        $accountA = Account::factory()->for($user)->create(['name' => 'Compte A']);
        $accountB = Account::factory()->for($user)->create(['name' => 'Compte B']);

        Transaction::factory()->for($user)->for($accountA)->create();
        Transaction::factory()->for($user)->for($accountB)->create();

        $this
            ->actingAs($user)
            ->get(route('transactions.index', ['account_id' => $accountA->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.account_id', $accountA->id));
    }

    public function test_category_filter_includes_descendants(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $parent = Category::factory()->for($user)->create(['name' => 'Alimentation']);
        $child = Category::factory()->for($user)->create([
            'name' => 'Courses',
            'parent_id' => $parent->id,
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'category_id' => $child->id,
            'label' => 'Enfant',
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'category_id' => null,
            'label' => 'Sans cat',
        ]);

        $this
            ->actingAs($user)
            ->get(route('transactions.index', ['category_id' => $parent->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.label', 'Enfant'));
    }

    public function test_amount_range_filter(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => '-120.00',
            'type' => TransactionType::Expense,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => '-15.00',
            'type' => TransactionType::Expense,
        ]);

        $this
            ->actingAs($user)
            ->get(route('transactions.index', [
                'amount_min' => -100,
                'amount_max' => 0,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.amount', -15));
    }

    public function test_user_can_update_category_inline(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();
        $transaction = Transaction::factory()->for($user)->for($account)->create([
            'category_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->patch(route('transactions.update-category', $transaction), [
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_list_query_completes_quickly_with_many_rows(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->count(500)->create();

        $started = microtime(true);

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.index', ['per_page' => 50]));

        $elapsedMs = (microtime(true) - $started) * 1000;

        $response->assertOk();
        $this->assertLessThan(
            500,
            $elapsedMs,
            'Expected indexed list query to complete within 500ms on SQLite test DB.',
        );
    }
}
