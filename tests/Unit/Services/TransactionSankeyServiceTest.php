<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\TransactionSankeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionSankeyServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sankey_shows_income_sources_expense_roots_and_subcategories(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        app()->setLocale('en');
        app(CategoryService::class)->seedDefaultsForUser($user);

        $account = Account::factory()->for($user)->create(['name' => 'Checking']);

        $salary = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'salary_income')
            ->firstOrFail();
        $bonuses = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'bonuses')
            ->firstOrFail();
        $rent = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'rent')
            ->firstOrFail();
        $supplies = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'supplies')
            ->firstOrFail();
        $groceries = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'groceries')
            ->firstOrFail();
        $diningOut = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'dining_out')
            ->firstOrFail();
        $fastFood = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'fast_food')
            ->firstOrFail();

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => 2000,
            'category_id' => $salary->id,
            'label' => 'Paycheck',
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => 500,
            'category_id' => $bonuses->id,
            'label' => 'Bonus',
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => -600,
            'category_id' => $rent->id,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => -400,
            'category_id' => $supplies->id,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => -500,
            'category_id' => $groceries->id,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => -500,
            'category_id' => $diningOut->id,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => -500,
            'category_id' => $fastFood->id,
        ]);

        $flow = app(TransactionSankeyService::class)->buildForUser($user, []);

        $sourceNames = $this->nodeNamesByCategory($flow, 'source');
        $landingNames = $this->nodeNamesByCategory($flow, 'landing');
        $outcomeNames = $this->nodeNamesByCategory($flow, 'outcome');

        $this->assertContains('Salary', $sourceNames);
        $this->assertContains('Bonuses', $sourceNames);
        $this->assertContains('Home & personal', $landingNames);
        $this->assertContains('Food & beverage', $landingNames);
        $this->assertContains('Rent', $outcomeNames);
        $this->assertContains('Supplies', $outcomeNames);
        $this->assertContains('Groceries', $outcomeNames);
        $this->assertContains('Dining out', $outcomeNames);
        $this->assertContains('Fast food', $outcomeNames);

        $this->assertNull(collect($flow['nodes'])->firstWhere('kind', 'account'));

        $middleToRight = collect($flow['links'])
            ->filter(static fn (array $link): bool => isset($flow['nodes'][$link['source']], $flow['nodes'][$link['target']])
                && $flow['nodes'][$link['source']]['category'] === 'landing'
                && $flow['nodes'][$link['target']]['category'] === 'outcome');

        $this->assertTrue($middleToRight->contains(static fn (array $link): bool => $link['value'] === 600.0));
        $this->assertTrue($middleToRight->contains(static fn (array $link): bool => $link['value'] === 400.0));
        $this->assertTrue($middleToRight->contains(static fn (array $link): bool => $link['value'] === 500.0));
    }

    public function test_sankey_excludes_debit_only_or_income_only_data(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        app(CategoryService::class)->seedDefaultsForUser($user);

        $account = Account::factory()->for($user)->create();
        $groceries = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'groceries')
            ->firstOrFail();
        $salary = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'salary_income')
            ->firstOrFail();

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => -40,
            'category_id' => $groceries->id,
        ]);

        $debitOnly = app(TransactionSankeyService::class)->buildForUser($user, []);
        $this->assertSame([], $debitOnly['nodes']);
        $this->assertSame([], $debitOnly['links']);

        Transaction::query()->delete();

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => 1200,
            'category_id' => $salary->id,
        ]);

        $incomeOnly = app(TransactionSankeyService::class)->buildForUser($user, []);
        $this->assertSame([], $incomeOnly['nodes']);
        $this->assertSame([], $incomeOnly['links']);
    }

    public function test_sankey_rolls_deep_categories_up_to_direct_subcategory(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        app()->setLocale('en');
        app(CategoryService::class)->seedDefaultsForUser($user);

        $account = Account::factory()->for($user)->create(['name' => 'Checking']);
        $salary = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'salary_income')
            ->firstOrFail();
        $foodRoot = Category::query()
            ->where('user_id', $user->id)
            ->where('slug', 'food_and_beverage')
            ->firstOrFail();

        $mid = Category::query()->create([
            'user_id' => $user->id,
            'parent_id' => $foodRoot->id,
            'name' => 'Mid level',
            'color' => '#ffffff',
            'sort_order' => 99,
        ]);

        $deep = Category::query()->create([
            'user_id' => $user->id,
            'parent_id' => $mid->id,
            'name' => 'Deep level',
            'color' => '#ffffff',
            'sort_order' => 100,
        ]);

        Transaction::factory()->for($user)->for($account)->create([
            'amount' => 100,
            'category_id' => $salary->id,
        ]);
        Transaction::factory()->for($user)->for($account)->create([
            'amount' => -25,
            'category_id' => $deep->id,
        ]);

        $flow = app(TransactionSankeyService::class)->buildForUser($user, []);

        $outcomeNames = $this->nodeNamesByCategory($flow, 'outcome');

        $this->assertContains('Mid level', $outcomeNames);
        $this->assertNotContains('Deep level', $outcomeNames);
    }

    /**
     * @param  array{nodes: list<array{category: string, name: string}>, links: list<array{source: int, target: int, value: float}>}  $flow
     *
     * @return list<string>
     */
    private function nodeNamesByCategory(array $flow, string $category): array
    {
        return array_values(array_map(
            static fn (array $node): string => $node['name'],
            array_filter($flow['nodes'], static fn (array $node): bool => $node['category'] === $category),
        ));
    }
}
