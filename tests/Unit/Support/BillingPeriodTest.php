<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\BillingPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BillingPeriodTest extends TestCase
{
    public function test_clamps_start_day_in_february(): void
    {
        $this->assertSame(28, BillingPeriod::clampStartDay(31, 2023, 2));
        $this->assertSame(29, BillingPeriod::clampStartDay(31, 2024, 2));
        $this->assertSame(30, BillingPeriod::clampStartDay(31, 2024, 4));
    }

    public function test_bounds_for_day_twenty_seven(): void
    {
        $bounds = BillingPeriod::boundsContaining(
            CarbonImmutable::parse('2026-05-15'),
            27,
        );

        $this->assertSame('2026-04-27', $bounds['start']->format('Y-m-d'));
        $this->assertSame('2026-05-26', $bounds['end']->format('Y-m-d'));
    }

    public function test_bounds_for_day_thirty_one_in_leap_february(): void
    {
        $bounds = BillingPeriod::boundsContaining(
            CarbonImmutable::parse('2024-03-15'),
            31,
        );

        $this->assertSame('2024-02-29', $bounds['start']->format('Y-m-d'));
        $this->assertSame('2024-03-30', $bounds['end']->format('Y-m-d'));
    }

    #[DataProvider('recentPeriodsProvider')]
    public function test_recent_periods_are_contiguous(
        string $reference,
        int $monthStartDay,
        int $count,
    ): void {
        $periods = BillingPeriod::recentPeriodsChronological(
            $monthStartDay,
            $count,
            CarbonImmutable::parse($reference),
        );

        $this->assertCount($count, $periods);

        for ($index = 1; $index < $count; $index++) {
            $previousEnd = $periods[$index - 1]['end'];
            $currentStart = $periods[$index]['start'];

            $this->assertTrue(
                $previousEnd->addDay()->isSameDay($currentStart),
                "Gap between periods ending {$previousEnd->toDateString()} and starting {$currentStart->toDateString()}",
            );
        }
    }

    /**
     * @return array<string, array{string, int, int}>
     */
    public static function recentPeriodsProvider(): array
    {
        return [
            'calendar month' => ['2026-05-15', 1, 3],
            'day twenty seven' => ['2026-05-15', 27, 3],
            'day thirty one leap year' => ['2024-03-15', 31, 3],
        ];
    }
}
