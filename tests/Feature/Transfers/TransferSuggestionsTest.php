<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\DismissedTransferSuggestion;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CashflowSummaryService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfers_page_lists_suggestions(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->for($user)->create();
        $to = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($from)->create([
            'date' => '2024-06-01',
            'amount' => '-200.00',
            'label' => 'Transfer test',
        ]);
        Transaction::factory()->for($user)->for($to)->create([
            'date' => '2024-06-01',
            'amount' => '200.00',
            'label' => 'Transfer test',
        ]);

        $this
            ->actingAs($user)
            ->get(route('transfers.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('transfers/transfers-index')
                ->has('suggestions', 1));
    }

    public function test_user_can_accept_transfer_suggestion(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->for($user)->create();
        $to = Account::factory()->for($user)->create();

        $outgoing = Transaction::factory()->for($user)->for($from)->create([
            'date' => '2024-06-01',
            'amount' => '-120.00',
        ]);
        $incoming = Transaction::factory()->for($user)->for($to)->create([
            'date' => '2024-06-01',
            'amount' => '120.00',
        ]);

        $this
            ->actingAs($user)
            ->post(route('transfers.accept'), [
                'outgoing_transaction_id' => $outgoing->id,
                'incoming_transaction_id' => $incoming->id,
            ])
            ->assertRedirect(route('transfers.index'));

        $outgoing->refresh();
        $incoming->refresh();

        $this->assertSame(TransactionType::Transfer, $outgoing->type);
        $this->assertSame(TransactionType::Transfer, $incoming->type);
        $this->assertSame($incoming->id, $outgoing->transfer_pair_id);
        $this->assertSame($outgoing->id, $incoming->transfer_pair_id);
    }

    public function test_user_can_dismiss_transfer_suggestion(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->for($user)->create();
        $to = Account::factory()->for($user)->create();

        $outgoing = Transaction::factory()->for($user)->for($from)->create([
            'date' => '2024-06-01',
            'amount' => '-90.00',
        ]);
        $incoming = Transaction::factory()->for($user)->for($to)->create([
            'date' => '2024-06-01',
            'amount' => '90.00',
        ]);

        $this
            ->actingAs($user)
            ->post(route('transfers.dismiss'), [
                'outgoing_transaction_id' => $outgoing->id,
                'incoming_transaction_id' => $incoming->id,
            ])
            ->assertRedirect(route('transfers.index'));

        [$a, $b] = DismissedTransferSuggestion::canonicalPairIds($outgoing->id, $incoming->id);

        $this->assertDatabaseHas('dismissed_transfer_suggestions', [
            'user_id' => $user->id,
            'transaction_a_id' => $a,
            'transaction_b_id' => $b,
        ]);
    }

    public function test_user_can_create_manual_transfer_pair(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->for($user)->create([
            'initial_balance' => '1000.00',
            'current_balance' => '1000.00',
        ]);
        $to = Account::factory()->for($user)->create([
            'initial_balance' => '500.00',
            'current_balance' => '500.00',
        ]);

        $this
            ->actingAs($user)
            ->post(route('transfers.store'), [
                'from_account_id' => $from->id,
                'to_account_id' => $to->id,
                'date' => '2024-07-01',
                'label' => 'Épargne',
                'amount' => '100',
            ])
            ->assertRedirect(route('transfers.index'));

        $this->assertSame(2, Transaction::query()->where('user_id', $user->id)->count());

        $from->refresh();
        $to->refresh();

        $this->assertSame('900.00', $from->current_balance);
        $this->assertSame('600.00', $to->current_balance);
    }

    public function test_cashflow_totals_exclude_transfers(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-08-10',
            'amount' => '1000.00',
            'type' => TransactionType::Income,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-08-12',
            'amount' => '-50.00',
            'type' => TransactionType::Expense,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'date' => '2024-08-15',
            'amount' => '500.00',
            'type' => TransactionType::Transfer,
        ]);

        $totals = app(CashflowSummaryService::class)->totalsForPeriod(
            $user,
            CarbonImmutable::parse('2024-08-01'),
            CarbonImmutable::parse('2024-08-31'),
        );

        $this->assertSame('1000.00', $totals['income']);
        $this->assertSame('-50.00', $totals['expense']);
        $this->assertSame('950.00', $totals['net']);
    }
}
