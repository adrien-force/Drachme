<?php

declare(strict_types=1);

namespace Tests\Feature\Recurring;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\DismissedRecurringPattern;
use App\Models\RecurringPattern;
use App\Models\Transaction;
use App\Models\User;
use App\Support\RecurringLabelNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringPatternTest extends TestCase
{
    use RefreshDatabase;

    public function test_recurring_page_lists_suggestions(): void
    {
        $user = User::factory()->create();
        $this->seedNetflixTransactions($user);

        $this
            ->actingAs($user)
            ->get(route('recurring.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('recurring/recurring-index')
                ->has('suggestions.data', 1)
                ->has('confirmed.data', 0)
                ->has('summary')
                ->has('filters')
                ->has('categoryOptions'));
    }

    public function test_user_can_confirm_recurring_suggestion(): void
    {
        $user = User::factory()->create();
        $account = $this->seedNetflixTransactions($user);
        $labelPattern = app(RecurringLabelNormalizer::class)->normalize('NETFLIX ABONNEMENT');

        $this
            ->actingAs($user)
            ->from(route('recurring.index'))
            ->post(route('recurring.confirm'), [
                'label_pattern' => $labelPattern,
                'display_label' => 'NETFLIX ABONNEMENT',
                'expected_amount' => '15.99',
                'frequency' => 'monthly',
                'transaction_type' => 'expense',
                'occurrence_count' => 3,
                'account_id' => $account->id,
                'category_id' => null,
            ])
            ->assertRedirect(route('recurring.index'));

        $this->assertDatabaseHas('recurring_patterns', [
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
            'display_label' => 'NETFLIX ABONNEMENT',
            'is_confirmed' => true,
        ]);
    }

    public function test_user_can_dismiss_recurring_suggestion(): void
    {
        $user = User::factory()->create();
        $this->seedNetflixTransactions($user);
        $labelPattern = app(RecurringLabelNormalizer::class)->normalize('NETFLIX ABONNEMENT');

        $this
            ->actingAs($user)
            ->from(route('recurring.index'))
            ->post(route('recurring.dismiss'), [
                'label_pattern' => $labelPattern,
                'transaction_type' => 'expense',
            ])
            ->assertRedirect(route('recurring.index'));

        $this->assertDatabaseHas('dismissed_recurring_patterns', [
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
        ]);
    }

    public function test_user_can_destroy_confirmed_pattern(): void
    {
        $user = User::factory()->create();
        $account = $this->seedNetflixTransactions($user);
        $labelPattern = app(RecurringLabelNormalizer::class)->normalize('NETFLIX ABONNEMENT');

        $pattern = RecurringPattern::query()->create([
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
            'display_label' => 'NETFLIX ABONNEMENT',
            'expected_amount' => '15.99',
            'frequency' => 'monthly',
            'transaction_type' => TransactionType::Expense,
            'account_id' => $account->id,
            'occurrence_count' => 3,
            'is_confirmed' => true,
        ]);

        $this
            ->actingAs($user)
            ->from(route('recurring.index'))
            ->delete(route('recurring.destroy', $pattern))
            ->assertRedirect(route('recurring.index'));

        $this->assertDatabaseMissing('recurring_patterns', [
            'id' => $pattern->id,
        ]);
    }

    public function test_transactions_list_shows_recurring_badge_after_confirm(): void
    {
        $user = User::factory()->create();
        $account = $this->seedNetflixTransactions($user);
        $labelPattern = app(RecurringLabelNormalizer::class)->normalize('NETFLIX ABONNEMENT');

        RecurringPattern::query()->create([
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
            'display_label' => 'NETFLIX ABONNEMENT',
            'expected_amount' => '15.99',
            'frequency' => 'monthly',
            'transaction_type' => TransactionType::Expense,
            'account_id' => $account->id,
            'occurrence_count' => 3,
            'is_confirmed' => true,
        ]);

        $this
            ->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('transactions.data', 3)
                ->where('transactions.data.0.recurring_display_label', 'NETFLIX ABONNEMENT')
                ->where('transactions.data.1.recurring_display_label', 'NETFLIX ABONNEMENT')
                ->where('transactions.data.2.recurring_display_label', 'NETFLIX ABONNEMENT'));
    }

    private function seedNetflixTransactions(User $user): Account
    {
        $account = Account::factory()->for($user)->create();

        foreach (['2026-01-15', '2026-02-14', '2026-03-15'] as $date) {
            Transaction::factory()->for($user)->for($account)->create([
                'date' => $date,
                'label' => 'NETFLIX ABONNEMENT',
                'amount' => '-15.99',
                'type' => TransactionType::Expense,
            ]);
        }

        return $account;
    }
}
