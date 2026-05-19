<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class LoanAmortizationService
{
    /**
     * @return array{
     *     principal: float,
     *     annual_rate: float,
     *     start_date: string,
     *     end_date: string,
     *     term_months: int,
     *     monthly_payment: float,
     *     total_interest: float,
     *     total_cost: float,
     *     installments: list<array{
     *         date: string,
     *         payment: float,
     *         interest: float,
     *         principal: float,
     *         balance: float,
     *     }>,
     *     chart_points: list<array{date: string, balance: float}>,
     * }|null
     */
    public function planForAccount(Account $account): ?array
    {
        if (! $this->isLoanAccount($account)) {
            return null;
        }

        $principal = (float) ($account->loan_original_principal ?? $account->initial_balance);

        if ($principal <= 0) {
            return null;
        }

        $annualRate = $account->loan_interest_rate !== null
            ? (float) $account->loan_interest_rate
            : null;

        if ($annualRate === null) {
            return null;
        }

        $startDate = $this->resolveStartDate($account);
        $endDate = $this->resolveEndDate($account);

        if ($startDate === null || $endDate === null) {
            return null;
        }

        if ($endDate->lessThanOrEqualTo($startDate)) {
            return null;
        }

        return $this->buildPlan(
            $principal,
            $annualRate,
            $startDate,
            $endDate,
            $account->payment_day,
        );
    }

    public function outstandingBalanceForAccount(Account $account, ?CarbonImmutable $asOf = null): ?float
    {
        $plan = $this->planForAccount($account);

        if ($plan === null) {
            return null;
        }

        return $this->outstandingAt($plan, $asOf ?? CarbonImmutable::today());
    }

    /**
     * @param  array{
     *     installments: list<array{date: string, balance: float}>,
     * }  $plan
     */
    public function outstandingAt(array $plan, CarbonImmutable $asOf): float
    {
        $asOf = $asOf->startOfDay();
        $principal = (float) ($plan['chart_points'][0]['balance'] ?? $plan['principal']);

        if ($plan['installments'] === []) {
            return $principal;
        }

        $firstDate = CarbonImmutable::parse($plan['installments'][0]['date'])->startOfDay();

        if ($asOf->lessThan($firstDate)) {
            return $principal;
        }

        $previousBalance = $principal;

        foreach ($plan['installments'] as $installment) {
            $date = CarbonImmutable::parse($installment['date'])->startOfDay();

            if ($asOf->lessThan($date)) {
                return $previousBalance;
            }

            $previousBalance = (float) $installment['balance'];
        }

        return max(0.0, $previousBalance);
    }

    /**
     * @return array{
     *     principal: float,
     *     annual_rate: float,
     *     start_date: string,
     *     end_date: string,
     *     term_months: int,
     *     monthly_payment: float,
     *     total_interest: float,
     *     total_cost: float,
     *     installments: list<array{
     *         date: string,
     *         payment: float,
     *         interest: float,
     *         principal: float,
     *         balance: float,
     *     }>,
     *     chart_points: list<array{date: string, balance: float}>,
     * }
     */
    public function buildPlan(
        float $principal,
        float $annualRatePercent,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $paymentDay = null,
    ): array {
        $firstPayment = $this->firstPaymentDate($startDate, $endDate, $paymentDay);
        $termMonths = $this->countPaymentMonths($firstPayment, $endDate);

        if ($termMonths < 1) {
            throw new InvalidArgumentException('loan_term_too_short');
        }

        $monthlyRate = $annualRatePercent / 100 / 12;
        $monthlyPayment = $this->monthlyPayment($principal, $monthlyRate, $termMonths);

        $balance = round($principal, 2);
        $installments = [];
        $totalInterest = 0.0;
        $cursor = $firstPayment;

        for ($index = 0; $index < $termMonths && $balance > 0; $index++) {
            $interest = round($balance * $monthlyRate, 2);
            $isLast = $index === $termMonths - 1 || $cursor->greaterThanOrEqualTo($endDate->startOfDay());
            $principalPart = round($monthlyPayment - $interest, 2);

            if ($isLast || $principalPart >= $balance) {
                $principalPart = $balance;
                $payment = round($principalPart + $interest, 2);
                $isLast = true;
            } else {
                $payment = $monthlyPayment;
            }

            $balance = round($balance - $principalPart, 2);
            $totalInterest += $interest;

            $installments[] = [
                'date' => $cursor->format('Y-m-d'),
                'payment' => $payment,
                'interest' => $interest,
                'principal' => $principalPart,
                'balance' => max(0.0, $balance),
            ];

            if ($isLast) {
                break;
            }

            $cursor = $this->addMonthPreservingDay($cursor, $paymentDay ?? $cursor->day);
        }

        $chartPoints = [
            [
                'date' => $startDate->format('Y-m-d'),
                'balance' => round($principal, 2),
            ],
        ];

        foreach ($installments as $installment) {
            $chartPoints[] = [
                'date' => $installment['date'],
                'balance' => $installment['balance'],
            ];
        }

        return [
            'principal' => round($principal, 2),
            'annual_rate' => $annualRatePercent,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'term_months' => count($installments),
            'monthly_payment' => $monthlyPayment,
            'total_interest' => round($totalInterest, 2),
            'total_cost' => round($principal + $totalInterest, 2),
            'installments' => $installments,
            'chart_points' => $chartPoints,
        ];
    }

    public function monthlyPayment(float $principal, float $monthlyRate, int $termMonths): float
    {
        if ($termMonths < 1) {
            return round($principal, 2);
        }

        if ($monthlyRate <= 0.0) {
            return round($principal / $termMonths, 2);
        }

        $factor = (1 + $monthlyRate) ** $termMonths;

        return round($principal * $monthlyRate * $factor / ($factor - 1), 2);
    }

    private function isLoanAccount(Account $account): bool
    {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);

        return $type === AccountType::Loan;
    }

    private function resolveStartDate(Account $account): ?CarbonImmutable
    {
        $openedAt = $account->opened_at;

        if ($openedAt instanceof \DateTimeInterface) {
            return CarbonImmutable::parse($openedAt->format('Y-m-d'))->startOfDay();
        }

        if ($openedAt !== null && $openedAt !== '') {
            return CarbonImmutable::parse((string) $openedAt)->startOfDay();
        }

        return null;
    }

    private function resolveEndDate(Account $account): ?CarbonImmutable
    {
        $end = $account->loan_end_date;

        if ($end instanceof \DateTimeInterface) {
            return CarbonImmutable::parse($end->format('Y-m-d'))->startOfDay();
        }

        if ($end !== null && $end !== '') {
            return CarbonImmutable::parse((string) $end)->startOfDay();
        }

        return null;
    }

    private function firstPaymentDate(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        ?int $paymentDay,
    ): CarbonImmutable {
        $day = $paymentDay ?? $startDate->day;
        $candidate = $startDate->setDay(min($day, $startDate->daysInMonth));

        if ($candidate->lessThan($startDate)) {
            $candidate = $this->addMonthPreservingDay($candidate, $day);
        }

        if ($candidate->greaterThan($endDate)) {
            return $endDate;
        }

        return $candidate;
    }

    private function countPaymentMonths(CarbonImmutable $firstPayment, CarbonImmutable $endDate): int
    {
        $count = 0;
        $cursor = $firstPayment->startOfDay();
        $end = $endDate->startOfDay();

        while ($cursor->lessThanOrEqualTo($end)) {
            $count++;
            $cursor = $cursor->addMonth();
        }

        return max(1, $count);
    }

    private function addMonthPreservingDay(CarbonImmutable $date, int $day): CarbonImmutable
    {
        $next = $date->addMonth();

        return $next->setDay(min($day, $next->daysInMonth));
    }
}
