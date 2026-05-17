<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BalanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_recalculate_is_idempotent(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '100.00',
            'current_balance' => '100.00',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => '-25.00',
        ]);

        $engine = app(BalanceEngine::class);
        $engine->recalculateAccount($account);
        $account->refresh();
        $first = $account->current_balance;

        $engine->recalculateAccount($account);
        $account->refresh();

        $this->assertSame($first, $account->current_balance);
        $this->assertSame('75.00', $account->current_balance);
    }

    public function test_transaction_chain_updates_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '1000.00',
            'current_balance' => '1000.00',
        ]);

        Transaction::factory()->for($user)->for($account)->create(['amount' => '200.00']);
        Transaction::factory()->for($user)->for($account)->create(['amount' => '-50.00']);
        Transaction::factory()->for($user)->for($account)->create(['amount' => '-30.00']);

        app(BalanceEngine::class)->recalculateAccount($account);
        $account->refresh();

        $this->assertSame('1120.00', $account->current_balance);
    }

    public function test_recalculate_completes_quickly_with_many_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '0.00',
            'current_balance' => '0.00',
        ]);

        Transaction::factory()->for($user)->for($account)->count(500)->create();

        $started = microtime(true);
        app(BalanceEngine::class)->recalculateAccount($account);
        $elapsedMs = (microtime(true) - $started) * 1000;

        $this->assertLessThan(
            100,
            $elapsedMs,
            'Expected account balance recalculation to stay under 100ms on SQLite.',
        );
    }
}
