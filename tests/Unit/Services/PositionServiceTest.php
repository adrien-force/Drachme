<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use App\Services\PositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PositionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_value_uses_last_price_when_present(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'quantity' => '10',
            'average_price' => '50',
            'last_price' => '60',
        ]);

        $service = app(PositionService::class);

        $this->assertSame(600.0, $service->marketValue($position));
        $this->assertFalse($service->usesAveragePrice($position));
    }

    public function test_market_value_falls_back_to_average_price(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Invest]);
        $position = Position::factory()->for($user)->for($account)->create([
            'quantity' => '4',
            'average_price' => '25.5',
            'last_price' => null,
        ]);

        $service = app(PositionService::class);

        $this->assertSame(102.0, $service->marketValue($position));
        $this->assertTrue($service->usesAveragePrice($position));
    }

    public function test_create_rejects_non_invest_account(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create(['type' => AccountType::Checking]);

        $this->expectException(InvalidArgumentException::class);

        app(PositionService::class)->create($user, $account, [
            'isin' => 'FR0012633286',
            'label' => 'Test',
            'quantity' => '1',
            'average_price' => '10',
        ]);
    }
}
