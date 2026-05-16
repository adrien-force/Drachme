<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_manual_transaction_and_balance_updates(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '1000.00',
            'current_balance' => '1000.00',
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'account_id' => $account->id,
                'date' => '2024-03-15',
                'label' => 'Courses',
                'amount' => '-42.50',
            ])
            ->assertRedirect(route('accounts.show', $account));

        $transaction = Transaction::query()->first();
        $this->assertNotNull($transaction);
        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('-42.50', $transaction->amount);

        $account->refresh();
        $this->assertSame('957.50', $account->current_balance);
    }

    public function test_amount_zero_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'account_id' => $account->id,
                'date' => '2024-03-15',
                'label' => 'Zero',
                'amount' => '0',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_user_can_update_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '0.00',
            'current_balance' => '0.00',
        ]);

        $transaction = Transaction::factory()->for($user)->for($account)->create([
            'amount' => '-10.00',
            'type' => TransactionType::Expense,
        ]);

        $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $account->id,
                'date' => '2024-04-01',
                'label' => 'Updated label',
                'amount' => '25.00',
            ])
            ->assertRedirect(route('accounts.show', $account));

        $transaction->refresh();
        $this->assertSame('Updated label', $transaction->label);
        $this->assertSame('25.00', $transaction->amount);
        $this->assertSame(TransactionType::Income, $transaction->type);
    }

    public function test_user_cannot_update_another_users_transaction(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = Account::factory()->for($owner)->create();
        $transaction = Transaction::factory()->for($owner)->for($account)->create();

        $this
            ->actingAs($intruder)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $account->id,
                'date' => '2024-04-01',
                'label' => 'Hack',
                'amount' => '10.00',
            ])
            ->assertForbidden();
    }

    public function test_transactions_index_is_accessible(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('transactions/transactions-index'));
    }
}
