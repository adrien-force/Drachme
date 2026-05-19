<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Enums\AccountType;
use App\Enums\InvestKind;
use App\Models\Account;
use App\Models\User;
use App\Support\CommodityIsin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommodityPositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_commodity_position_with_grams_and_price_per_gram(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'invest_kind' => InvestKind::Commodities,
        ]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'label' => 'Or',
                'quantity' => '150.5',
                'average_price' => '62.40',
                'last_price' => '65.10',
            ])
            ->assertRedirect(route('positions.index', $account));

        $this->assertDatabaseHas('positions', [
            'account_id' => $account->id,
            'label' => 'Or',
            'isin' => CommodityIsin::fromLabel('Or'),
            'quantity' => '150.500000',
            'average_price' => '62.400000',
            'last_price' => '65.100000',
            'market_symbol' => null,
        ]);
    }

    public function test_commodity_position_accepts_quantity_with_five_decimal_places(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'invest_kind' => InvestKind::Commodities,
        ]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'label' => 'Or',
                'quantity' => '10.12345',
                'average_price' => '60',
            ])
            ->assertRedirect(route('positions.index', $account));

        $this->assertDatabaseHas('positions', [
            'account_id' => $account->id,
            'quantity' => '10.123450',
        ]);
    }

    public function test_commodity_position_rejects_quantity_with_more_than_six_decimal_places(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'invest_kind' => InvestKind::Commodities,
        ]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'label' => 'Or',
                'quantity' => '10.1234567',
                'average_price' => '60',
            ])
            ->assertSessionHasErrors('quantity');
    }

    public function test_commodity_position_can_store_market_symbol(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'invest_kind' => InvestKind::Commodities,
        ]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'label' => 'Or',
                'quantity' => '10',
                'average_price' => '60',
                'market_symbol' => 'XAUEUR',
            ])
            ->assertRedirect(route('positions.index', $account));

        $this->assertDatabaseHas('positions', [
            'account_id' => $account->id,
            'label' => 'Or',
            'market_symbol' => 'XAUEUR',
        ]);
    }

    public function test_invest_kind_cannot_change_when_positions_exist(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
            'invest_kind' => InvestKind::Commodities,
        ]);

        $this
            ->actingAs($user)
            ->post(route('positions.store', $account), [
                'label' => 'Or',
                'quantity' => '10',
                'average_price' => '60',
            ])
            ->assertRedirect();

        $this
            ->actingAs($user)
            ->put(route('accounts.update', $account), [
                'name' => $account->name,
                'type' => AccountType::Invest->value,
                'invest_kind' => InvestKind::Securities->value,
            ])
            ->assertSessionHasErrors('invest_kind');
    }
}
