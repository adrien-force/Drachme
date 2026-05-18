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

class TransactionTriageTest extends TestCase
{
    use RefreshDatabase;

    public function test_triage_page_shows_first_uncategorized_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create(['name' => 'Courses']);

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'CARREFOUR PARIS',
            'amount' => '-42.50',
            'category_id' => null,
            'date' => '2026-05-01',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'SALAIRE',
            'amount' => '2000.00',
            'category_id' => $category->id,
            'date' => '2026-05-02',
        ]);

        $this->actingAs($user)
            ->get(route('transactions.triage'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('transactions/transactions-triage')
                ->where('transaction.label', 'CARREFOUR PARIS')
                ->where('totalUncategorized', 1));
    }

    public function test_categorize_creates_rule_and_auto_tags_similar_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create(['name' => 'Courses']);

        $first = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'CARREFOUR PARIS 01',
            'amount' => '-10.00',
            'category_id' => null,
            'date' => '2026-05-01',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'CARREFOUR PARIS 02',
            'amount' => '-20.00',
            'category_id' => null,
            'date' => '2026-05-02',
        ]);

        $this->actingAs($user)
            ->post(route('transactions.triage.process', $first), [
                'action' => 'categorize',
                'category_id' => $category->id,
                'create_rule' => true,
                'selected_tokens' => ['carrefour', 'paris'],
                'skip_ids' => [],
            ])
            ->assertRedirect(route('transactions.triage'));

        $first->refresh();
        $this->assertSame($category->id, $first->category_id);

        $this->assertTrue(
            CategoryRule::query()
                ->where('user_id', $user->id)
                ->where('pattern', 'carrefour paris')
                ->exists(),
        );

        $this->assertSame(
            0,
            Transaction::query()
                ->where('user_id', $user->id)
                ->whereNull('category_id')
                ->count(),
        );
    }

    public function test_skip_advances_to_next_transaction(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();

        $first = Transaction::factory()->for($user)->for($account)->create([
            'label' => 'FIRST',
            'category_id' => null,
            'date' => '2026-05-01',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'SECOND',
            'category_id' => null,
            'date' => '2026-05-02',
        ]);

        $this->actingAs($user)
            ->post(route('transactions.triage.process', $first), [
                'action' => 'skip',
                'skip_ids' => [],
            ])
            ->assertRedirect(route('transactions.triage', [
                'skip_ids' => [$first->id],
            ]));
    }
}
