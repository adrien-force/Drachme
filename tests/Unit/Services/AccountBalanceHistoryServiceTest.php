<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\AccountBalanceHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountBalanceHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_daily_balance_points(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '100.00',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-02-05',
            'amount' => '25.00',
        ]);

        $service = app(AccountBalanceHistoryService::class);
        $history = $service->build($account, '2024-02-01', '2024-02-07');

        $this->assertSame('2024-02-01', $history['from']);
        $this->assertSame('2024-02-07', $history['to']);
        $this->assertCount(7, $history['points']);
        $this->assertSame(100.0, $history['points'][0]['balance']);
        $this->assertSame(100.0, $history['points'][3]['balance']);
        $this->assertSame(125.0, $history['points'][4]['balance']);
        $this->assertSame(125.0, $history['points'][6]['balance']);
        $this->assertFalse($history['is_all_time']);
    }

    public function test_all_time_starts_at_earliest_transaction_or_opening(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '100.00',
            'opened_at' => '2024-01-01',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-03-10',
            'amount' => '10.00',
        ]);

        $service = app(AccountBalanceHistoryService::class);
        $history = $service->build($account, null, null, true);

        $this->assertTrue($history['is_all_time']);
        $this->assertSame('2024-01-01', $history['from']);
    }
}
