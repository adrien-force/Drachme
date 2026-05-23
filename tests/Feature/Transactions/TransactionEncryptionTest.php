<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_label_is_stored_encrypted_and_readable_via_model(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $transaction = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Courses Carrefour Paris',
            'notes' => 'Note confidentielle',
        ]);

        $rawLabel = DB::table('transactions')->where('id', $transaction->id)->value('label');
        $this->assertIsString($rawLabel);
        $this->assertNotSame('Courses Carrefour Paris', $rawLabel);

        $fresh = Transaction::query()->findOrFail($transaction->id);
        $this->assertSame('Courses Carrefour Paris', $fresh->label);
        $this->assertSame('Note confidentielle', $fresh->notes);
    }

    public function test_transaction_search_matches_encrypted_label_tokens(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Courses Carrefour Paris',
            'amount' => -42.50,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'Salaire employeur',
            'amount' => 2500,
        ]);

        $results = app(TransactionListService::class)->paginateForUser($user, [
            'search' => 'carrefour',
            'per_page' => 25,
        ]);

        $this->assertCount(1, $results->items());
        $this->assertSame('Courses Carrefour Paris', $results->items()[0]->label);
    }
}
