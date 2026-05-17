<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\DismissedRecurringPattern;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RecurringDetector;
use App\Support\RecurringLabelNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_monthly_pattern_with_three_occurrences(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        foreach (['2026-01-15', '2026-02-14', '2026-03-15'] as $date) {
            Transaction::factory()->for($user)->for($account)->create([
                'date' => $date,
                'label' => 'NETFLIX ABONNEMENT',
                'amount' => '-15.99',
                'type' => TransactionType::Expense,
            ]);
        }

        $suggestions = app(RecurringDetector::class)->findSuggestions($user);

        $this->assertCount(1, $suggestions);
        $this->assertSame('NETFLIX ABONNEMENT', $suggestions[0]->displayLabel);
        $this->assertSame(RecurringFrequency::Monthly, $suggestions[0]->frequency);
        $this->assertSame('15.99', $suggestions[0]->expectedAmount);
        $this->assertGreaterThanOrEqual(60, $suggestions[0]->score);
    }

    public function test_dismissed_patterns_are_excluded(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $labelPattern = app(RecurringLabelNormalizer::class)->normalize('NETFLIX ABONNEMENT');

        foreach (['2026-01-15', '2026-02-14', '2026-03-15'] as $date) {
            Transaction::factory()->for($user)->for($account)->create([
                'date' => $date,
                'label' => 'NETFLIX ABONNEMENT',
                'amount' => '-15.99',
                'type' => TransactionType::Expense,
            ]);
        }

        DismissedRecurringPattern::query()->create([
            'user_id' => $user->id,
            'label_pattern' => $labelPattern,
            'transaction_type' => TransactionType::Expense,
        ]);

        $suggestions = app(RecurringDetector::class)->findSuggestions($user);

        $this->assertCount(0, $suggestions);
    }

    public function test_finds_biweekly_pattern(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        foreach (['2026-01-01', '2026-01-15', '2026-01-29'] as $date) {
            Transaction::factory()->for($user)->for($account)->create([
                'date' => $date,
                'label' => 'CRECHE HEBDO',
                'amount' => '-80.00',
                'type' => TransactionType::Expense,
            ]);
        }

        $suggestions = app(RecurringDetector::class)->findSuggestions($user);

        $this->assertCount(1, $suggestions);
        $this->assertSame(RecurringFrequency::Biweekly, $suggestions[0]->frequency);
    }

    public function test_generic_card_labels_are_excluded(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        foreach (['2026-01-15', '2026-02-14', '2026-03-15'] as $date) {
            Transaction::factory()->for($user)->for($account)->create([
                'date' => $date,
                'label' => 'CB',
                'amount' => '-42.50',
                'type' => TransactionType::Expense,
            ]);
        }

        $suggestions = app(RecurringDetector::class)->findSuggestions($user);

        $this->assertCount(0, $suggestions);
    }
}
