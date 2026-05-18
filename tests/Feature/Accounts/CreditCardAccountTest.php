<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CashflowSummaryService;
use App\Services\NetWorthSnapshotService;
use App\Services\TransferDetector;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_credit_card_account_linked_to_checking(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
            'name' => 'Compte courant',
        ]);

        $this->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Carte Visa',
                'type' => AccountType::CreditCard->value,
                'settlement_account_id' => $checking->id,
                'billing_day' => 5,
                'initial_balance' => '0',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'name' => 'Carte Visa',
            'type' => AccountType::CreditCard->value,
            'settlement_account_id' => $checking->id,
            'billing_day' => 5,
        ]);
    }

    public function test_credit_card_requires_settlement_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Carte Visa',
                'type' => AccountType::CreditCard->value,
                'initial_balance' => '0',
            ])
            ->assertSessionHasErrors('settlement_account_id');
    }

    public function test_cashflow_excludes_credit_card_transactions(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
        ]);

        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-03-01',
            'amount' => '-100.00',
            'type' => TransactionType::Expense,
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-03-02',
            'amount' => '-50.00',
            'type' => TransactionType::Expense,
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-31');

        $totals = app(CashflowSummaryService::class)->totalsForPeriod($user, $from, $to);

        $this->assertSame('-100.00', $totals['expense']);
    }

    public function test_credit_card_balance_excluded_from_net_worth(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create([
            'type' => AccountType::Checking,
            'initial_balance' => '5000.00',
            'current_balance' => '5000.00',
        ]);

        Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'initial_balance' => '0.00',
            'current_balance' => '-1200.00',
        ]);

        $totals = app(NetWorthSnapshotService::class)->totalsForUser($user);

        $this->assertSame('5000.00', $totals['net_worth']);
        $this->assertSame('5000.00', $totals['total_assets']);
        $this->assertSame('0.00', $totals['total_liabilities']);
    }

    public function test_transfer_detector_prioritizes_credit_card_settlement(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
        ]);

        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-03-05',
            'amount' => '-250.00',
            'label' => 'PRLV CB VISA',
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-03-05',
            'amount' => '250.00',
            'label' => 'Reglement releve',
        ]);

        $suggestions = app(TransferDetector::class)->findCandidates($user);

        $this->assertNotEmpty($suggestions);
        $this->assertGreaterThanOrEqual(80, $suggestions[0]->score);
    }

    public function test_transfer_detector_boosts_configured_settlement_label(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'settlement_label_pattern' => 'DEBIT DIFFERE',
        ]);

        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-04-10',
            'amount' => '-412.37',
            'label' => 'DEBIT DIFFERE N° 1234107',
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-04-11',
            'amount' => '412.37',
            'label' => 'Reglement',
        ]);

        $suggestions = app(TransferDetector::class)->findCandidates($user);

        $this->assertNotEmpty($suggestions);
        $this->assertGreaterThanOrEqual(170, $suggestions[0]->score);
    }
}
