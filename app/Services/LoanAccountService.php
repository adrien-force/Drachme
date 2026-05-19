<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;

class LoanAccountService
{
    public function __construct(
        private readonly LoanAmortizationService $amortization,
    ) {}

    public function syncBalances(Account $account): Account
    {
        if (! $this->isLoanAccount($account)) {
            return $account;
        }

        $plan = $this->amortization->planForAccount($account);

        if ($plan === null) {
            return $account;
        }

        $outstanding = $this->amortization->outstandingAt($plan, \Carbon\CarbonImmutable::today());

        $account->update([
            'loan_original_principal' => number_format($plan['principal'], 2, '.', ''),
            'loan_monthly_payment' => number_format($plan['monthly_payment'], 2, '.', ''),
            'initial_balance' => number_format($plan['principal'], 2, '.', ''),
            'current_balance' => number_format($outstanding, 2, '.', ''),
        ]);

        return $account->fresh() ?? $account;
    }

    /**
     * @return array{
     *     plan: array<string, mixed>|null,
     *     metrics: array<string, mixed>,
     * }
     */
    public function present(Account $account): array
    {
        $plan = $this->amortization->planForAccount($account);

        if ($plan === null) {
            return [
                'plan' => null,
                'metrics' => [
                    'can_calculate' => false,
                ],
            ];
        }

        $outstanding = $this->amortization->outstandingAt($plan, \Carbon\CarbonImmutable::today());
        $principalRepaid = max(0.0, $plan['principal'] - $outstanding);
        $monthsRemaining = $this->countRemainingInstallments($plan);

        return [
            'plan' => $plan,
            'metrics' => [
                'can_calculate' => true,
                'original_principal' => $plan['principal'],
                'outstanding_principal' => $outstanding,
                'principal_repaid' => round($principalRepaid, 2),
                'monthly_payment' => $plan['monthly_payment'],
                'term_months' => $plan['term_months'],
                'months_remaining' => $monthsRemaining,
                'total_interest' => $plan['total_interest'],
                'total_cost' => $plan['total_cost'],
                'interest_paid_to_date' => $this->interestPaidToDate($plan),
                'total_repaid' => round($principalRepaid + $this->interestPaidToDate($plan), 2),
            ],
        ];
    }

    /**
     * @param  array{installments: list<array{date: string}>}  $plan
     */
    private function countRemainingInstallments(array $plan): int
    {
        $today = \Carbon\CarbonImmutable::today()->startOfDay();
        $remaining = 0;

        foreach ($plan['installments'] as $installment) {
            $date = \Carbon\CarbonImmutable::parse($installment['date'])->startOfDay();

            if ($date->greaterThanOrEqualTo($today)) {
                $remaining++;
            }
        }

        return $remaining;
    }

    /**
     * @param  array{installments: list<array{date: string, interest: float}>}  $plan
     */
    private function interestPaidToDate(array $plan): float
    {
        $today = \Carbon\CarbonImmutable::today()->startOfDay();
        $paid = 0.0;

        foreach ($plan['installments'] as $installment) {
            $date = \Carbon\CarbonImmutable::parse($installment['date'])->startOfDay();

            if ($date->greaterThan($today)) {
                break;
            }

            $paid += (float) $installment['interest'];
        }

        return round($paid, 2);
    }

    private function isLoanAccount(Account $account): bool
    {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);

        return $type === AccountType::Loan;
    }
}
