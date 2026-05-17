<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\DismissedTransferSuggestion;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransferDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_finds_opposite_amount_pair_on_different_accounts(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->for($user)->create();
        $to = Account::factory()->for($user)->create();

        $outgoing = Transaction::factory()->for($user)->for($from)->create([
            'date' => '2024-06-01',
            'label' => 'Virement interne',
            'amount' => '-150.00',
        ]);
        $incoming = Transaction::factory()->for($user)->for($to)->create([
            'date' => '2024-06-02',
            'label' => 'Virement interne',
            'amount' => '150.00',
        ]);

        $suggestions = app(TransferDetector::class)->findCandidates($user);

        $this->assertCount(1, $suggestions);
        $this->assertSame($outgoing->id, $suggestions[0]->outgoing->id);
        $this->assertSame($incoming->id, $suggestions[0]->incoming->id);
        $this->assertGreaterThanOrEqual(75, $suggestions[0]->score);
    }

    public function test_dismissed_pairs_are_excluded(): void
    {
        $user = User::factory()->create();
        $from = Account::factory()->for($user)->create();
        $to = Account::factory()->for($user)->create();

        $outgoing = Transaction::factory()->for($user)->for($from)->create([
            'date' => '2024-06-01',
            'amount' => '-80.00',
        ]);
        $incoming = Transaction::factory()->for($user)->for($to)->create([
            'date' => '2024-06-01',
            'amount' => '80.00',
        ]);

        [$a, $b] = DismissedTransferSuggestion::canonicalPairIds($outgoing->id, $incoming->id);
        DismissedTransferSuggestion::query()->create([
            'user_id' => $user->id,
            'transaction_a_id' => $a,
            'transaction_b_id' => $b,
        ]);

        $suggestions = app(TransferDetector::class)->findCandidates($user);

        $this->assertCount(0, $suggestions);
    }
}
