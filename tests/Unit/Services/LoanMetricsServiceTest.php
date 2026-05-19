<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use App\Services\LoanMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_use_amortization_schedule(): void
    {
        $user = User::factory()->create();

        $loan = Account::factory()->for($user)->create([
            'type' => AccountType::Loan,
            'opened_at' => '2020-01-01',
            'loan_original_principal' => '100000',
            'loan_interest_rate' => '3',
            'loan_end_date' => now()->addYears(10)->format('Y-m-d'),
            'initial_balance' => '100000',
            'current_balance' => '100000',
        ]);

        $metrics = app(LoanMetricsService::class)->forAccount($loan);

        $this->assertGreaterThan(0, $metrics['monthly_payment']);
        $this->assertLessThan(100_000.0, $metrics['outstanding_principal']);
        $this->assertGreaterThan(0, $metrics['estimated_total_cost']);
    }
}
