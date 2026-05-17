<?php

declare(strict_types=1);

namespace Tests\Feature\Balance;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\NetWorthSnapshot;
use App\Models\User;
use App\Services\NetWorthSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetWorthSnapshotTest extends TestCase
{
    use RefreshDatabase;

    public function test_credit_account_reduces_net_worth(): void
    {
        $user = User::factory()->create();

        Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
            'initial_balance' => '10000.00',
            'current_balance' => '10000.00',
        ]);

        Account::factory()->for($user)->create([
            'type' => AccountType::Credit,
            'initial_balance' => '5000.00',
            'current_balance' => '5000.00',
        ]);

        $totals = app(NetWorthSnapshotService::class)->totalsForUser($user);

        $this->assertSame('10000.00', $totals['total_assets']);
        $this->assertSame('5000.00', $totals['total_liabilities']);
        $this->assertSame('5000.00', $totals['net_worth']);
    }

    public function test_record_command_persists_snapshot(): void
    {
        $user = User::factory()->create();

        Account::factory()->for($user)->create([
            'initial_balance' => '3000.00',
            'current_balance' => '3000.00',
        ]);

        $this->artisan('drachme:record-net-worth', [
            '--user' => (string) $user->id,
            '--date' => '2024-09-01',
        ])->assertSuccessful();

        $snapshot = NetWorthSnapshot::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '2024-09-01')
            ->first();

        $this->assertNotNull($snapshot);
        $this->assertSame('3000.00', $snapshot->net_worth);
        $this->assertIsArray($snapshot->breakdown);
    }
}
