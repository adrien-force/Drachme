<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\YahooChartFixture;
use Tests\TestCase;

class PositionMarketDataRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_refresh_single_position_price(): void
    {
        config(['market_data.enabled' => true]);

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'isin' => 'IE00B4L5Y983',
            'label' => 'MSCI World',
            'market_symbol' => 'IWDA.AS',
        ]);

        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response(
                YahooChartFixture::chart(88.5),
            ),
        ]);

        Cache::flush();

        $this->actingAs($user)
            ->post(route('positions.refresh-price', $position))
            ->assertRedirect(route('positions.show', $position))
            ->assertInertiaFlash('toast', [
                'type' => 'success',
                'message' => __('ui.positions.market_price_refreshed'),
            ]);

        $this->assertSame('88.500000', Position::query()->value('last_price'));
    }

    public function test_refresh_price_does_not_fail_when_market_data_log_unwritable(): void
    {
        config(['market_data.enabled' => true]);
        config(['logging.channels.market_data' => [
            'driver' => 'single',
            'path' => storage_path('logs/unwritable-market-data.log'),
            'level' => 'debug',
        ]]);

        if (is_file(storage_path('logs/unwritable-market-data.log'))) {
            @chmod(storage_path('logs/unwritable-market-data.log'), 0444);
        }

        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'isin' => 'IE00B4L5Y983',
            'label' => 'MSCI World',
            'market_symbol' => 'IWDA.AS',
        ]);

        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response(
                YahooChartFixture::chart(90.0),
            ),
        ]);

        Cache::flush();

        $this->actingAs($user)
            ->post(route('positions.refresh-price', $position))
            ->assertRedirect(route('positions.show', $position));

        $this->assertSame('90.000000', Position::query()->value('last_price'));
    }
}
