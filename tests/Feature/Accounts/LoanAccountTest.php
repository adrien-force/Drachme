<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_loan_account_with_payment_day(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Prêt immo',
                'type' => AccountType::Credit->value,
                'initial_balance' => '150000',
                'payment_day' => '5',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Prêt immo',
            'type' => AccountType::Credit->value,
            'initial_balance' => '150000.00',
            'current_balance' => '150000.00',
            'payment_day' => 5,
        ]);
    }

    public function test_payment_day_rejected_for_checking_account(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Courant',
                'type' => AccountType::Checking->value,
                'initial_balance' => '1000',
                'payment_day' => '10',
            ])
            ->assertSessionHasErrors('payment_day');
    }

    public function test_accounts_index_includes_payment_day_for_loan(): void
    {
        $user = User::factory()->create();
        Account::factory()->for($user)->create([
            'type' => AccountType::Credit,
            'current_balance' => '80000',
            'payment_day' => 12,
        ]);

        $this
            ->actingAs($user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('accounts.0.type', AccountType::Credit->value)
                ->where('accounts.0.current_balance', 80000)
                ->where('accounts.0.payment_day', 12));
    }
}
