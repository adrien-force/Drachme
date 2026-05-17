<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurringFrequency: string
{
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Bimonthly = 'bimonthly';
    case Quarterly = 'quarterly';
    case Biannual = 'biannual';
    case Yearly = 'yearly';

    public function targetDays(): int
    {
        return match ($this) {
            self::Weekly => 7,
            self::Biweekly => 14,
            self::Monthly => 30,
            self::Bimonthly => 60,
            self::Quarterly => 90,
            self::Biannual => 180,
            self::Yearly => 365,
        };
    }

    public function intervalMin(): int
    {
        return match ($this) {
            self::Weekly => 6,
            self::Biweekly => 12,
            self::Monthly => 25,
            self::Bimonthly => 53,
            self::Quarterly => 80,
            self::Biannual => 165,
            self::Yearly => 350,
        };
    }

    public function intervalMax(): int
    {
        return match ($this) {
            self::Weekly => 8,
            self::Biweekly => 16,
            self::Monthly => 35,
            self::Bimonthly => 67,
            self::Quarterly => 100,
            self::Biannual => 195,
            self::Yearly => 380,
        };
    }

    public function gapMatches(int $gapDays): bool
    {
        return $gapDays >= $this->intervalMin() && $gapDays <= $this->intervalMax();
    }
}
