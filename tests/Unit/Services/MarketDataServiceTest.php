<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use App\Services\MarketDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Support\YahooChartFixture;
use Tests\TestCase;

class MarketDataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'market_data.enabled' => true,
            'market_data.cache_ttl' => 3600,
        ]);
    }

    public function test_fetch_price_for_isin_uses_yahoo_quote(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response(
                YahooChartFixture::chart(102.5),
            ),
        ]);

        Cache::flush();

        $price = app(MarketDataService::class)->fetchPriceForIsin(
            'IE00B4L5Y983',
            'MSCI World',
            'IWDA.AS',
        );

        $this->assertSame('102.500000', $price);
    }

    public function test_fetch_price_resolves_symbol_from_isin_via_openfigi(): void
    {
        Http::fake([
            'api.openfigi.com/*' => Http::response(
                YahooChartFixture::openFigiMapping('WPEA', 'FP'),
            ),
            'query1.finance.yahoo.com/*' => Http::response(
                YahooChartFixture::chart(88.25),
            ),
        ]);

        Cache::flush();

        $price = app(MarketDataService::class)->fetchPriceForIsin('IE0002XZSHO1', 'MSCI World PEA');

        $this->assertSame('88.250000', $price);
        $this->assertDatabaseHas('isin_market_symbols', [
            'isin' => 'IE0002XZSHO1',
            'symbol' => 'WPEA.PA',
            'source' => 'openfigi',
        ]);
    }

    public function test_refresh_for_user_updates_positions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Invest,
        ]);

        Position::factory()->for($user)->for($account)->create([
            'isin' => 'IE00B4L5Y983',
            'label' => 'MSCI World',
            'market_symbol' => 'IWDA.AS',
            'last_price' => null,
            'last_price_at' => null,
        ]);

        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response(
                YahooChartFixture::chart(99.1),
            ),
        ]);

        Cache::flush();

        $result = app(MarketDataService::class)->refreshForUser($user);

        $this->assertSame(1, $result->updated);
        $this->assertSame('99.100000', Position::query()->value('last_price'));
        $this->assertNotNull(Position::query()->value('last_price_at'));
    }

    public function test_quota_response_stops_refresh_without_crash(): void
    {
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
            'query1.finance.yahoo.com/*' => Http::response([], 429),
        ]);

        Cache::flush();

        $result = app(MarketDataService::class)->refreshForUser($user);

        $this->assertNotNull($result->quotaMessage);
        $this->assertSame(0, $result->updated);
    }
}
