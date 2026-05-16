<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTransactionListTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_are_paginated(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->count(30)->create();

        $this
            ->actingAs($user)
            ->get(route('accounts.show', ['account' => $account, 'per_page' => 10]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 10)
                ->where('transactions.meta.total', 30)
                ->where('transactions.meta.per_page', 10)
                ->where('transactionFilters.per_page', 10));
    }

    public function test_search_filters_by_label(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create(['label' => 'Courses Carrefour']);
        Transaction::factory()->for($user)->for($account)->create(['label' => 'Salaire']);

        $this
            ->actingAs($user)
            ->get(route('accounts.show', ['account' => $account, 'search' => 'Carrefour']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.label', 'Courses Carrefour'));
    }

    public function test_search_is_case_insensitive(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create(['label' => 'Courses Carrefour']);

        $this
            ->actingAs($user)
            ->get(route('accounts.show', ['account' => $account, 'search' => 'carrefour']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.label', 'Courses Carrefour'));
    }

    public function test_flow_credit_filter_returns_positive_amounts_only(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => '120.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => '-45.00',
            'type' => TransactionType::Expense,
        ]);

        $this
            ->actingAs($user)
            ->get(route('accounts.show', ['account' => $account, 'flow' => 'credit']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 1)
                ->where('transactions.data.0.amount', 120));
    }

    public function test_sort_by_amount_ascending(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create(['amount' => '-10.00']);
        Transaction::factory()->for($user)->for($account)->create(['amount' => '50.00']);

        $this
            ->actingAs($user)
            ->get(route('accounts.show', [
                'account' => $account,
                'sort' => 'amount',
                'order' => 'asc',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('transactions.data.0.amount', -10)
                ->where('transactions.data.1.amount', 50));
    }
}
