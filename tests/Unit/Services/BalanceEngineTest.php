<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BalanceEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BalanceEngineTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function reconcile_actual_balance_adjusts_initial_balance(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create([
            'initial_balance' => '1000.00',
            'current_balance' => '950.00',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-01-10',
            'amount' => '-50.00',
            'type' => TransactionType::Expense,
        ]);

        app(BalanceEngine::class)->reconcileActualBalance($account, 1000.0);

        $account->refresh();

        $this->assertSame('1050.00', $account->initial_balance);
        $this->assertSame('1000.00', $account->current_balance);
    }
}
