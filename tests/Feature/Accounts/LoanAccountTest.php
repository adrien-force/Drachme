<?php

declare(strict_types=1);

namespace Tests\Feature\Accounts;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_loan_account_with_amortization(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Prêt immo',
                'type' => AccountType::Loan->value,
                'loan_original_principal' => '200000',
                'loan_interest_rate' => '3.25',
                'opened_at' => '2020-01-01',
                'loan_end_date' => '2045-01-01',
                'payment_day' => '5',
            ])
            ->assertRedirect();

        $account = Account::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($account);
        $this->assertSame(AccountType::Loan, $account->type);
        $this->assertNotNull($account->loan_monthly_payment);
        $this->assertGreaterThan(0, (float) $account->loan_monthly_payment);
        $this->assertLessThan(200_000.0, (float) $account->current_balance);
    }

    public function test_loan_requires_principal_rate_and_dates(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => 'Prêt incomplet',
                'type' => AccountType::Loan->value,
            ])
            ->assertSessionHasErrors([
                'loan_original_principal',
                'loan_interest_rate',
                'opened_at',
                'loan_end_date',
            ]);
    }

    public function test_account_show_includes_loan_amortization_chart(): void
    {
        $user = User::factory()->create();
        $loan = Account::factory()->for($user)->create([
            'type' => AccountType::Loan,
            'opened_at' => '2020-01-01',
            'initial_balance' => '100000',
            'current_balance' => '100000',
            'loan_original_principal' => '100000',
            'loan_interest_rate' => '4',
            'loan_end_date' => now()->addYears(15)->format('Y-m-d'),
        ]);

        $this
            ->actingAs($user)
            ->get(route('accounts.show', $loan))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('account.type', AccountType::Loan->value)
                ->has('loanAmortization.plan.chart_points')
                ->where('loanAmortization.metrics.can_calculate', true)
                ->where('account.loan_metrics.monthly_payment', fn ($value) => $value > 0));
    }
}
