<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final class DashboardDateRange
{
    public const PRESET_3M = '3m';

    public const PRESET_6M = '6m';

    public const PRESET_12M = '12m';

    public const PRESET_YTD = 'ytd';

    public const PRESET_CUSTOM = 'custom';

    public const PRESET_ALL = 'all';

    private const VALID_PRESETS = [
        self::PRESET_3M,
        self::PRESET_6M,
        self::PRESET_12M,
        self::PRESET_YTD,
        self::PRESET_ALL,
        self::PRESET_CUSTOM,
    ];

    public function __construct(
        public readonly CarbonImmutable $from,
        public readonly CarbonImmutable $to,
        public readonly string $preset,
    ) {}

    public static function fromRequest(Request $request, int $monthStartDay, User $user): self
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

        if ($preset === self::PRESET_ALL) {
            return new self(self::resolveAllTimeStart($user), $today, $preset);
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

    private static function resolveAllTimeStart(User $user): CarbonImmutable
    {
        $candidates = [];

        $earliestOpenedAt = Account::query()
            ->where('user_id', $user->id)
            ->whereNotNull('opened_at')
            ->min('opened_at');

        if ($earliestOpenedAt !== null) {
            $candidates[] = CarbonImmutable::parse((string) $earliestOpenedAt)->startOfDay();
        }

        $firstTransactionDate = Transaction::query()
            ->where('user_id', $user->id)
            ->min('date');

        if ($firstTransactionDate !== null) {
            $candidates[] = CarbonImmutable::parse((string) $firstTransactionDate)->startOfDay();
        }

        $firstPortfolioImport = PortfolioSnapshot::query()
            ->where('user_id', $user->id)
            ->min('imported_at');

        if ($firstPortfolioImport !== null) {
            $candidates[] = CarbonImmutable::parse((string) $firstPortfolioImport)->startOfDay();
        }

        if ($candidates === []) {
            return CarbonImmutable::today();
        }

        $start = $candidates[0];

        foreach ($candidates as $candidate) {
            if ($candidate->lessThan($start)) {
                $start = $candidate;
            }
        }

        return $start;
    }
}
