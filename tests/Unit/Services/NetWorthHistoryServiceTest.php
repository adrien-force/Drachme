<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\NetWorthHistoryService;
use App\Support\BillingPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetWorthHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_reflects_transaction_balances_over_time(): void
    {
        CarbonImmutable::setTestNow('2026-05-15');

        $user = User::factory()->create(['month_start_day' => 1]);
        $account = Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
            'initial_balance' => '1000.00',
            'current_balance' => '1000.00',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-03-10',
            'amount' => '500.00',
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2026-05-05',
            'amount' => '200.00',
        ]);

        $to = CarbonImmutable::today();
        $from = BillingPeriod::recentPeriodsChronological(1, 4, $to)[0]['start'];

        $points = app(NetWorthHistoryService::class)->pointsForUser($user, $from, $to, 1);

        $this->assertNotEmpty($points);
        $this->assertSame(1000.0, $points[0]['value']);
        $this->assertSame(1700.0, $points[array_key_last($points)]['value']);

        CarbonImmutable::setTestNow();
    }
}
