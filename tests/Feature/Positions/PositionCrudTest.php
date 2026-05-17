<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PositionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_positions_on_invest_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        Position::factory()->for($user)->for($account)->create([
            'isin' => 'FR0012633286',
            'label' => 'Lyxor World',
            'quantity' => '5',
            'average_price' => '100',
        ]);

        $this
            ->actingAs($user)
            ->get(route('positions.index', $account))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('positions/positions-index')
                ->has('positions', 1)
                ->where('positions.0.isin', 'FR0012633286')
                ->where('positions.0.market_value', 500));
    }

    public function test_non_invest_account_positions_index_is_forbidden(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        $this
            ->actingAs($user)
            ->get(route('positions.index', $account))
            ->assertForbidden();
    }

    public function test_user_can_create_position(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'isin' => 'fr0012633286',
                'label' => 'Lyxor World',
                'quantity' => '2.5',
                'average_price' => '80',
            ])
            ->assertRedirect(route('positions.index', $account));

        $this->assertDatabaseHas('positions', [
            'account_id' => $account->id,
            'isin' => 'FR0012633286',
            'label' => 'Lyxor World',
        ]);
    }

    public function test_cannot_create_position_on_checking_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'isin' => 'FR0012633286',
                'label' => 'Lyxor World',
                'quantity' => '1',
                'average_price' => '10',
            ])
            ->assertForbidden();
    }

    public function test_invalid_isin_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'isin' => 'BAD',
                'label' => 'Invalid',
                'quantity' => '1',
                'average_price' => '10',
            ])
            ->assertSessionHasErrors('isin');
    }

    public function test_duplicate_isin_on_same_account_is_rejected(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        Position::factory()->for($user)->for($account)->create(['isin' => 'FR0012633286']);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'isin' => 'FR0012633286',
                'label' => 'Duplicate',
                'quantity' => '1',
                'average_price' => '10',
            ])
            ->assertSessionHasErrors('isin');
    }

    public function test_investments_index_lists_invest_accounts(): void
    {
        $user = User::factory()->create();
        $invest = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'name' => 'PEA',
        ]);
        Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        Position::factory()->for($user)->for($invest)->create([
            'quantity' => '2',
            'average_price' => '50',
        ]);

        $this
            ->actingAs($user)
            ->get(route('investments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('investments/investments-index')
                ->has('accounts', 1)
                ->where('accounts.0.name', 'PEA')
                ->where('accounts.0.positions_value', 100));
    }
}
