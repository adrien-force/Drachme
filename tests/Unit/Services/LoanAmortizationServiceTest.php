<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\LoanAmortizationService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class LoanAmortizationServiceTest extends TestCase
{
    public function test_monthly_payment_matches_standard_amortization(): void
    {
        $service = new LoanAmortizationService;

        $payment = $service->monthlyPayment(200_000.0, 0.035 / 12, 240);

        $this->assertEqualsWithDelta(1_159.92, $payment, 1.0);
    }

    public function test_plan_generates_schedule_and_outstanding_at_today(): void
    {
        $service = new LoanAmortizationService;

        $start = CarbonImmutable::parse('2020-01-05');
        $end = CarbonImmutable::parse('2040-01-05');

        $plan = $service->buildPlan(100_000.0, 3.0, $start, $end, 5);

        $this->assertGreaterThan(200, count($plan['installments']));
        $this->assertSame(100_000.0, $plan['chart_points'][0]['balance']);
        $this->assertEqualsWithDelta($plan['monthly_payment'], $plan['installments'][0]['payment'], 50.0);

        $outstanding = $service->outstandingAt($plan, CarbonImmutable::parse('2030-06-01'));

        $this->assertGreaterThan(0.0, $outstanding);
        $this->assertLessThan(100_000.0, $outstanding);
    }

    public function test_outstanding_is_zero_after_loan_end(): void
    {
        $service = new LoanAmortizationService;

        $start = CarbonImmutable::parse('2020-01-01');
        $end = CarbonImmutable::parse('2025-01-01');

        $plan = $service->buildPlan(50_000.0, 2.5, $start, $end, 1);
        $outstanding = $service->outstandingAt($plan, CarbonImmutable::parse('2026-01-01'));

        $this->assertSame(0.0, $outstanding);
    }
}
