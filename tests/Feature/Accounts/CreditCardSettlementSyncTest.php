<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Enums\AccountType;
use App\Enums\SettlementPeriodMode;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditCardSettlementService;
use App\Services\CreditCardSettlementSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditCardSettlementSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_links_and_marks_settlement_from_checking_label(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'settlement_label_pattern' => 'DEBIT DIFFERE',
        ]);

        $checkingDebit = Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-03-05',
            'amount' => '-412.37',
            'label' => 'DEBIT DIFFERE N° 1234',
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-03-05',
            'amount' => '412.37',
            'label' => 'Reglement releve',
            'is_card_settlement' => false,
        ]);

        Transaction::factory()->for($user)->for($card)->create([
            'date' => '2026-03-01',
            'amount' => '-50.00',
            'label' => 'Courses',
        ]);

        $result = app(CreditCardSettlementSyncService::class)->syncForCard($card);

        $this->assertSame(1, $result->linkedPairs);
        $this->assertSame(1, $result->markedSettlements);

        $checkingDebit->refresh();

        $this->assertTrue($checkingDebit->is_card_settlement);
        $this->assertNotNull($checkingDebit->transfer_pair_id);
        $this->assertNotNull($checkingDebit->card_period_start);
    }

    public function test_sync_marks_checking_debit_without_card_credit(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'settlement_label_pattern' => 'DEBIT DIFFERE',
            'billing_day' => 4,
            'settlement_period_mode' => SettlementPeriodMode::BillingCycle,
        ]);

        foreach (range(1, 10) as $day) {
            Transaction::factory()->for($user)->for($card)->create([
                'date' => sprintf('2026-03-%02d', $day + 5),
                'amount' => '-50.00',
                'label' => "Achat {$day}",
            ]);
        }

        $checkingDebit = Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-04-04',
            'amount' => '-500.00',
            'label' => 'DEBIT DIFFERE N° 4107',
        ]);

        $result = app(CreditCardSettlementSyncService::class)->syncForCard($card);

        $this->assertSame(0, $result->linkedPairs);
        $this->assertSame(1, $result->markedSettlements);
        $this->assertSame(0, $result->skippedNoMatch);

        $checkingDebit->refresh();

        $this->assertTrue($checkingDebit->is_card_settlement);
        $this->assertSame('2026-03-05', $checkingDebit->card_period_start?->format('Y-m-d'));

        $category = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', Category::SLUG_CARD_SETTLEMENT)
            ->first();

        $this->assertNotNull($category);
        $this->assertSame($category->id, $checkingDebit->category_id);

        $data = app(CreditCardSettlementService::class)->build($card);

        $this->assertCount(1, $data['settlements']);
        $settlement = $data['settlements'][0];
        $this->assertSame(500.0, $settlement['amount']);
        $this->assertSame(500.0, $settlement['spend_total']);
        $this->assertCount(10, $settlement['purchases']);
        $this->assertTrue($settlement['spend_matches_settlement']);
    }

    public function test_sync_endpoint_returns_success(): void
    {
        $user = User::factory()->create();
        $checking = Account::factory()->for($user)->create(['type' => AccountType::Checking]);
        $card = Account::factory()->for($user)->create([
            'type' => AccountType::CreditCard,
            'settlement_account_id' => $checking->id,
            'settlement_label_pattern' => 'DEBIT DIFFERE',
        ]);

        Transaction::factory()->for($user)->for($checking)->create([
            'date' => '2026-04-01',
            'amount' => '-100.00',
            'label' => 'DEBIT DIFFERE',
        ]);

        $this->actingAs($user)
            ->post(route('accounts.sync-settlements', $card))
            ->assertRedirect();

        $this->assertTrue(
            (bool) Transaction::query()
                ->where('account_id', $checking->id)
                ->where('is_card_settlement', true)
                ->exists(),
        );
    }
}
