<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RecurringLabelNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRecurringTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_transaction_as_recurring(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $labelPattern = app(RecurringLabelNormalizer::class)->normalize('Spotify Premium');

        $transaction = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Spotify Premium',
            'amount' => '-9.99',
            'type' => TransactionType::Expense,
        ]);

        $this
            ->actingAs($user)
            ->from(route('transactions.index', ['edit_transaction' => $transaction->id]))
            ->post(route('transactions.mark-recurring', $transaction), [
                'frequency' => 'monthly',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recurring_patterns', [
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
            'display_label' => 'Spotify Premium',
            'expected_amount' => '9.99',
            'frequency' => 'monthly',
            'is_confirmed' => true,
        ]);
    }

    public function test_user_can_unmark_transaction_recurring(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $labelPattern = app(RecurringLabelNormalizer::class)->normalize('Spotify Premium');

        $transaction = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Spotify Premium',
            'amount' => '-9.99',
            'type' => TransactionType::Expense,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.mark-recurring', $transaction), [
                'frequency' => 'monthly',
            ]);

        $this
            ->actingAs($user)
            ->from(route('transactions.index', ['edit_transaction' => $transaction->id]))
            ->delete(route('transactions.unmark-recurring', $transaction))
            ->assertRedirect();

        $this->assertDatabaseMissing('recurring_patterns', [
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
        ]);
    }

    public function test_transfer_transaction_cannot_be_marked_recurring(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $transaction = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Virement interne',
            'amount' => '-100.00',
            'type' => TransactionType::Transfer,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.mark-recurring', $transaction), [
                'frequency' => 'monthly',
            ])
            ->assertSessionHasErrors('transaction');
    }
}
