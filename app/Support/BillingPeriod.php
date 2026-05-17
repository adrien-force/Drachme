<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Billing month boundaries when the period does not start on the 1st.
 * Start day is clamped to the last day of short months (e.g. 31 → 28/29 in February).
 */
final class BillingPeriod
{
    public const int MIN_START_DAY = 1;

    public const int MAX_START_DAY = 31;

    public static function normalizeStartDay(int $monthStartDay): int
    {
        return max(self::MIN_START_DAY, min(self::MAX_START_DAY, $monthStartDay));
    }

    public static function clampStartDay(int $monthStartDay, int $year, int $month): int
    {
        $monthStartDay = self::normalizeStartDay($monthStartDay);

        return min(
            $monthStartDay,
            CarbonImmutable::createFromDate($year, $month, 1)->daysInMonth,
        );
    }

    /**
     * @return array{start: CarbonImmutable, end: CarbonImmutable}
     */
    public static function boundsContaining(CarbonImmutable $date, int $monthStartDay): array
    {
        $monthStartDay = self::normalizeStartDay($monthStartDay);
        $date = $date->startOfDay();

        $anchorThisMonth = self::clampStartDay(
            $monthStartDay,
            (int) $date->format('Y'),
            (int) $date->format('m'),
        );

        if ($date->day >= $anchorThisMonth) {
            $start = $date->copy()->day($anchorThisMonth);
        } else {
            $previous = $date->copy()->subMonthNoOverflow();
            $anchorPrevious = self::clampStartDay(
                $monthStartDay,
                (int) $previous->format('Y'),
                (int) $previous->format('m'),
            );
            $start = $previous->copy()->day($anchorPrevious);
        }

        $nextMonth = $start->copy()->addMonthNoOverflow();
        $nextAnchor = self::clampStartDay(
            $monthStartDay,
            (int) $nextMonth->format('Y'),
            (int) $nextMonth->format('m'),
        );
        $end = $nextMonth->copy()->day($nextAnchor)->subDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    public static function recentPeriodsChronological(
        int $monthStartDay,
        int $count,
        ?CarbonImmutable $reference = null,
    ): array {
        $count = max(1, $count);
        $reference = ($reference ?? CarbonImmutable::today())->startOfDay();

        $current = self::boundsContaining($reference, $monthStartDay);
        $periods = [$current];

        while (count($periods) < $count) {
            $dayBefore = $periods[array_key_last($periods)]['start']->subDay();
            $periods[] = self::boundsContaining($dayBefore, $monthStartDay);
        }

        return array_reverse($periods);
    }

    public static function periodKey(CarbonImmutable $start, CarbonImmutable $end): string
    {
        return $start->format('Y-m-d').'|'.$end->format('Y-m-d');
    }

    public static function formatLabel(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $locale,
    ): string {
        $previousLocale = CarbonImmutable::getLocale();
        CarbonImmutable::setLocale($locale);

        $isCalendarMonth = $start->day === 1
            && $end->isSameDay($start->endOfMonth());

        $label = $isCalendarMonth
            ? $start->isoFormat('MMM Y')
            : $start->isoFormat('D MMM').' – '.$end->isoFormat('D MMM Y');

        CarbonImmutable::setLocale($previousLocale);

        return $label;
    }
}
