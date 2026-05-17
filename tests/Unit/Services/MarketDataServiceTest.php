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
use Tests\TestCase;

class MarketDataServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'alpha_vantage.api_key' => 'test-key',
            'alpha_vantage.cache_ttl' => 3600,
        ]);
    }

    public function test_fetch_price_for_isin_uses_global_quote(): void
    {
        Http::fake([
            'www.alphavantage.co/query*' => Http::sequence()
                ->push([
                    'bestMatches' => [
                        [
                            '1. symbol' => 'IWDA.AS',
                            '2. name' => 'iShares Core MSCI World',
                        ],
                    ],
                ])
                ->push([
                    'Global Quote' => [
                        '05. price' => '102.500000',
                    ],
                ]),
        ]);

        Cache::flush();

        $price = app(MarketDataService::class)->fetchPriceForIsin(
            'IE00B4L5Y983',
            'MSCI World',
        );

        $this->assertSame('102.500000', $price);
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
            'last_price' => null,
            'last_price_at' => null,
        ]);

        Http::fake([
            'www.alphavantage.co/query*' => Http::sequence()
                ->push([
                    'bestMatches' => [
                        ['1. symbol' => 'IWDA.AS', '2. name' => 'MSCI World'],
                    ],
                ])
                ->push([
                    'Global Quote' => ['05. price' => '99.100000'],
                ]),
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
        ]);

        Http::fake([
            'www.alphavantage.co/query*' => Http::response([
                'Note' => 'Thank you for using Alpha Vantage! Our standard API call frequency is 5 calls per minute and 500 calls per day.',
            ]),
        ]);

        Cache::flush();

        $result = app(MarketDataService::class)->refreshForUser($user);

        $this->assertNotNull($result->quotaMessage);
        $this->assertSame(0, $result->updated);
    }
}
