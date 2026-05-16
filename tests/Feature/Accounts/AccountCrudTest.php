<?php

namespace Tests\Feature\Accounts;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_account_with_initial_balance_copied_to_current(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Compte courant',
                'institution' => 'BNP',
                'type' => AccountType::Checking->value,
                'initial_balance' => '1250.50',
                'opened_at' => '2024-01-15',
            ])
            ->assertRedirect(route('accounts.index'));

        $account = Account::query()->first();

        $this->assertNotNull($account);
        $this->assertSame($user->id, $account->user_id);
        $this->assertSame('1250.50', $account->initial_balance);
        $this->assertSame('1250.50', $account->current_balance);
        $this->assertFalse($account->is_archived);
    }

    public function test_user_can_update_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['name' => 'Old name']);

        $this
            ->actingAs($user)
            ->put(route('accounts.update', $account), [
                'name' => 'New name',
                'institution' => 'Revolut',
                'type' => AccountType::Savings->value,
            ])
            ->assertRedirect(route('accounts.index'));

        $this->assertSame('New name', $account->fresh()->name);
        $this->assertSame(AccountType::Savings, $account->fresh()->type);
    }

    public function test_user_can_archive_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this
            ->actingAs($user)
            ->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'));

        $this->assertTrue($account->fresh()->is_archived);
    }

    public function test_archived_accounts_are_hidden_from_index(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->create(['name' => 'Active']);
        Account::factory()->for($user)->archived()->create(['name' => 'Archived']);

        $this
            ->actingAs($user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('accounts/accounts-index')
                ->has('accounts', 1)
                ->where('accounts.0.name', 'Active'));
    }

    public function test_user_cannot_update_another_users_account(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = Account::factory()->for($owner)->create();

        $this
            ->actingAs($intruder)
            ->put(route('accounts.update', $account), [
                'name' => 'Hacked',
                'type' => AccountType::Checking->value,
            ])
            ->assertForbidden();
    }
}
