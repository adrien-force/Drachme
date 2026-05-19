<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;

class LoanMetricsService
{
    public function __construct(
        private readonly LoanAccountService $loanAccounts,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forAccount(Account $account): array
    {
        $presentation = $this->loanAccounts->present($account);

        if (! ($presentation['metrics']['can_calculate'] ?? false)) {
            return $this->emptyMetrics();
        }

        /** @var array<string, mixed> $metrics */
        $metrics = $presentation['metrics'];

        return [
            'original_principal' => (float) $metrics['original_principal'],
            'outstanding_principal' => (float) $metrics['outstanding_principal'],
            'principal_repaid' => (float) $metrics['principal_repaid'],
            'total_repaid' => (float) $metrics['total_repaid'],
            'interest_paid_estimate' => (float) $metrics['interest_paid_to_date'],
            'monthly_payment' => (float) $metrics['monthly_payment'],
            'months_remaining' => (int) $metrics['months_remaining'],
            'estimated_remaining_interest' => max(
                0.0,
                (float) $metrics['total_interest'] - (float) $metrics['interest_paid_to_date'],
            ),
            'estimated_total_cost' => (float) $metrics['total_cost'],
            'term_months' => (int) $metrics['term_months'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyMetrics(): array
    {
        return [
            'original_principal' => 0.0,
            'outstanding_principal' => 0.0,
            'principal_repaid' => 0.0,
            'total_repaid' => 0.0,
            'interest_paid_estimate' => 0.0,
            'monthly_payment' => null,
            'months_remaining' => null,
            'estimated_remaining_interest' => null,
            'estimated_total_cost' => null,
            'term_months' => null,
        ];
    }
}
