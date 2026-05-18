<?php

declare(strict_types=1);

namespace Tests\Feature\Investments;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\YahooChartFixture;
use Tests\TestCase;

class MarketDataRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_trigger_price_refresh_from_investments_page(): void
    {
        config(['market_data.enabled' => true]);

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        Position::factory()->for($user)->for($account)->create([
            'isin' => 'IE00B4L5Y983',
            'label' => 'MSCI World',
            'market_symbol' => 'IWDA.AS',
        ]);

        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response(
                YahooChartFixture::chart(50.0),
            ),
        ]);

        Cache::flush();

        $this->actingAs($user)
            ->post(route('investments.refresh-prices'))
            ->assertRedirect(route('investments.index'));

        $this->assertSame('50.000000', Position::query()->value('last_price'));
    }

    public function test_refresh_when_market_data_disabled_shows_error(): void
    {
        config(['market_data.enabled' => false]);

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        Position::factory()->for($user)->for($account)->create([
            'isin' => 'IE00B4L5Y983',
            'label' => 'MSCI World',
        ]);

        $this->actingAs($user)
            ->post(route('investments.refresh-prices'))
            ->assertRedirect(route('investments.index'))
            ->assertInertiaFlash('toast', [
                'type' => 'error',
                'message' => __('ui.investments.market_data_not_configured'),
            ]);
    }

    public function test_investments_page_exposes_market_data_configuration_flag(): void
    {
        config(['market_data.enabled' => true]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('investments.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('investments/investments-index')
                ->where('marketDataConfigured', true));
    }
}
