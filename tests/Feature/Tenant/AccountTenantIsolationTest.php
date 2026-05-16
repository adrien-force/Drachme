<?php

namespace Tests\Feature\Tenant;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_edit_another_users_account(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $account = Account::factory()->for($owner)->create();

        $this
            ->actingAs($intruder)
            ->get(route('accounts.edit', $account))
            ->assertForbidden();
    }

    public function test_user_can_edit_own_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $this
            ->actingAs($user)
            ->get(route('accounts.edit', $account))
            ->assertOk();
    }

    public function test_account_queries_are_scoped_to_authenticated_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Account::factory()->for($userA)->count(2)->create();
        Account::factory()->for($userB)->create();

        $this->actingAs($userA);

        $this->assertCount(2, Account::query()->get());
    }

    public function test_creating_account_assigns_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $account = Account::query()->create([
            'name' => 'Test account',
            'type' => 'checking',
            'initial_balance' => 0,
            'current_balance' => 0,
            'currency' => 'EUR',
        ]);

        $this->assertSame($user->id, $account->user_id);
    }
}
