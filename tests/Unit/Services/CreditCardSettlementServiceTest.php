<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Enums\SettlementPeriodMode;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardSettlementService;
use App\Services\TransferService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardSettlementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_groups_purchases_between_settlements(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'opened_at' => '2026-01-01',
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-01-10',
            'amount' => '-50.00',
            'label' => 'Courses',
        ]);
        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-01-25',
            'amount' => '-30.00',
            'label' => 'Restaurant',
        ]);
        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-02-01',
            'amount' => '80.00',
            'label' => 'Reglement releve',
            'is_card_settlement' => true,
            'card_period_start' => '2026-01-01',
        ]);
        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-02-15',
            'amount' => '-20.00',
            'label' => 'Essence',
        ]);

        $data = app(CreditCardSettlementService::class)->build($card);

        $this->assertCount(1, $data['settlements']);
        $settlement = $data['settlements'][0];
        $this->assertSame(80.0, $settlement['amount']);
        $this->assertSame(80.0, $settlement['spend_total']);
        $this->assertCount(2, $settlement['purchases']);
        $this->assertSame(20.0, $data['open_period']['spend_total']);
        $this->assertCount(1, $data['open_period']['purchases']);
    }

    public function test_includes_checking_label_when_transfer_linked(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'settlement_label_pattern' => 'DEBIT DIFFERE',
        ]);

        $outgoing = Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-03-05',
            'amount' => '-250.00',
            'label' => 'DEBIT DIFFERE',
            'is_card_settlement' => true,
        ]);
        $incoming = Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-03-05',
            'amount' => '250.00',
            'label' => 'Reglement',
        ]);

        app(TransferService::class)->linkPair($user, $outgoing, $incoming);

        $data = app(CreditCardSettlementService::class)->build($card);

        $this->assertTrue($data['settlements'][0]['is_linked']);
        $this->assertSame(250.0, $data['settlements'][0]['amount']);
        $this->assertSame('DEBIT DIFFERE', $data['settlements'][0]['checking_label']);
        $this->assertSame($checking->id, $data['settlements'][0]['account_id']);
    }

    public function test_manual_period_start_overrides_auto_window(): void
    {
        $user = User::factory()->create();
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'opened_at' => '2026-01-01',
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-01-05',
            'amount' => '-100.00',
        ]);
        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-02-01',
            'amount' => '50.00',
            'is_card_settlement' => true,
            'card_period_start' => '2026-01-20',
        ]);

        $data = app(CreditCardSettlementService::class)->build($card);

        $this->assertSame('2026-01-20', $data['settlements'][0]['period_start']);
        $this->assertTrue($data['settlements'][0]['period_start_is_manual']);
        $this->assertCount(0, $data['settlements'][0]['purchases']);
    }

    public function test_open_period_excludes_prior_billing_cycle_purchases(): void
    {
        CarbonImmutable::setTestNow('2026-05-17');

        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'settlement_label_pattern' => 'DEBIT DIFFERE',
            'billing_day' => 4,
            'settlement_period_mode' => SettlementPeriodMode::BillingCycle,
        ]);

        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-04-04',
            'amount' => '-500.00',
            'label' => 'DEBIT DIFFERE',
            'is_card_settlement' => true,
            'card_period_start' => '2026-03-05',
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-04-10',
            'amount' => '-50.00',
            'label' => 'Courses avril',
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-05-10',
            'amount' => '-30.00',
            'label' => 'Courses mai',
        ]);

        $data = app(CreditCardSettlementService::class)->build($card);

        $this->assertNotNull($data['open_period']);
        $this->assertSame(30.0, $data['open_period']['spend_total']);
        $this->assertSame('2026-05-05', $data['open_period']['period_start']);
        $this->assertCount(1, $data['open_period']['purchases']);

        CarbonImmutable::setTestNow();
    }
}
