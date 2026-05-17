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

class ApplyCategoryRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_apply_rules_to_uncategorized_transactions(): void
    {
        $user = User::factory()->create();
        $account = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create(['name' => 'Courses']);

        CategoryRule::factory()->for($user)->for($category)->create([
            'pattern' => 'carrefour',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'CB CARREFOUR',
            'category_id' => null,
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'label' => 'SALAIRE',
            'category_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.apply-category-rules'))
            ->assertRedirect();

        $this->assertSame(1, Transaction::query()->whereNotNull('category_id')->count());
        $this->assertSame(
            $category->id,
            Transaction::query()->where('label', 'CB CARREFOUR')->value('category_id'),
        );
        $this->assertNull(
            Transaction::query()->where('label', 'SALAIRE')->value('category_id'),
        );
    }

    public function test_apply_rules_can_be_scoped_to_account(): void
    {
        $user = User::factory()->create();
        $accountA = Account::factory()->for($user)->create();
        $accountB = Account::factory()->for($user)->create();
        $category = Category::factory()->for($user)->create();

        CategoryRule::factory()->for($user)->for($category)->create([
            'pattern' => 'leclerc',
        ]);

        Transaction::factory()->for($user)->for($accountA)->create([
            'label' => 'LECLERC DRIVE',
            'category_id' => null,
        ]);

        Transaction::factory()->for($user)->for($accountB)->create([
            'label' => 'LECLERC CITY',
            'category_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.apply-category-rules'), [
                'account_id' => $accountA->id,
            ])
            ->assertRedirect();

        $this->assertNotNull(
            Transaction::query()->where('account_id', $accountA->id)->value('category_id'),
        );
        $this->assertNull(
            Transaction::query()->where('account_id', $accountB->id)->value('category_id'),
        );
    }
}
