<?php

declare(strict_types=1);

namespace Tests\Feature\Transactions;

use App\Models\Account;
use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionBulkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_bulk_assign_category(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();
        $transactions = Transaction::factory()->for($user)->for($account)->count(2)->create([
            'category_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.bulk.category'), [
                'transaction_ids' => $transactions->pluck('id')->all(),
                'category_id' => $category->id,
            ])
            ->assertRedirect();

        foreach ($transactions as $transaction) {
            $this->assertSame(
                $category->id,
                $transaction->fresh()?->category_id,
            );
        }
    }

    public function test_user_can_apply_rules_to_selected_transactions_only(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        CategoryRule::factory()->for($user)->for($category)->create([
            'pattern' => 'netflix',
        ]);

        $target = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'PRLV NETFLIX',
            'category_id' => null,
        ]);

        $other = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'PRLV NETFLIX AUTRE COMPTE',
            'category_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.bulk.apply-rules'), [
                'transaction_ids' => [$target->id],
            ])
            ->assertRedirect();

        $this->assertSame($category->id, $target->fresh()?->category_id);
        $this->assertNull($other->fresh()?->category_id);
    }

    public function test_bulk_delete_skips_linked_transfers(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $deletable = Transaction::factory()->for($user)->for($account)->create([
            'transfer_pair_id' => null,
        ]);

        $pair = Transaction::factory()->for($user)->for($account)->create([
            'transfer_pair_id' => null,
        ]);

        $linked = Transaction::factory()->for($user)->for($account)->create([
            'transfer_pair_id' => $pair->id,
        ]);
        $pair->update(['transfer_pair_id' => $linked->id]);

        $this
            ->actingAs($user)
            ->delete(route('transactions.bulk.destroy'), [
                'transaction_ids' => [$deletable->id, $linked->id],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('transactions', ['id' => $deletable->id]);
        $this->assertDatabaseHas('transactions', ['id' => $linked->id]);
    }
}
