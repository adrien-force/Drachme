<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class DashboardDateRange
{
    public const PRESET_3M = '3m';

    public const PRESET_6M = '6m';

    public const PRESET_12M = '12m';

    public const PRESET_YTD = 'ytd';

    public const PRESET_CUSTOM = 'custom';

    private const VALID_PRESETS = [
        self::PRESET_3M,
        self::PRESET_6M,
        self::PRESET_12M,
        self::PRESET_YTD,
        self::PRESET_CUSTOM,
    ];

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly string $preset,
    ) {}

    public static function fromRequest(Request $request, int $monthStartDay): self
    {
        $today = CarbonImmutable::today();
        $monthStartDay = BillingPeriod::normalizeStartDay($monthStartDay);

        $fromInput = $request->string('from')->toString();
        $toInput = $request->string('to')->toString();

        if ($fromInput !== '' && $toInput !== '') {
            $from = CarbonImmutable::parse($fromInput)->startOfDay();
            $to = CarbonImmutable::parse($toInput)->startOfDay();

            if ($to->greaterThan($today)) {
                $to = $today;
            }

            if ($from->greaterThan($to)) {
                $from = $to;
            }

            return new self($from, $to, self::PRESET_CUSTOM);
        }

        $preset = $request->string('preset')->toString();
        if (! in_array($preset, self::VALID_PRESETS, true) || $preset === self::PRESET_CUSTOM) {
            $preset = self::PRESET_12M;
        }

        if ($preset === self::PRESET_YTD) {
            $from = CarbonImmutable::createFromDate($today->year, 1, 1)->startOfDay();
            $current = BillingPeriod::boundsContaining($today, $monthStartDay);

            if ($from->greaterThan($current['start'])) {
                $from = $current['start'];
            }

            return new self($from, $today, $preset);
        }

        $periodCount = match ($preset) {
            self::PRESET_3M => 3,
            self::PRESET_6M => 6,
            default => 12,
        };

        $periods = BillingPeriod::recentPeriodsChronological($monthStartDay, $periodCount, $today);

        return new self($periods[0]['start'], $today, $preset);
    }

    /**
     * @return array{preset: string, from: string, to: string}
     */
    public function toArray(): array
    {
        return [
            'preset' => $this->preset,
            'from' => $this->from->toDateString(),
            'to' => $this->to->toDateString(),
        ];
    }
}
