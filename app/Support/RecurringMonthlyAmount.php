<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;

final class RecurringMonthlyAmount
{
    public static function normalize(float $absAmount, RecurringFrequency $frequency): float
    {
        return match ($frequency) {
            RecurringFrequency::Weekly => $absAmount * 52 / 12,
            RecurringFrequency::Biweekly => $absAmount * 26 / 12,
            RecurringFrequency::Monthly => $absAmount,
            RecurringFrequency::Bimonthly => $absAmount / 2,
            RecurringFrequency::Quarterly => $absAmount / 3,
            RecurringFrequency::Biannual => $absAmount / 6,
            RecurringFrequency::Yearly => $absAmount / 12,
        };
    }

    public static function signed(float $absAmount, TransactionType $type): float
    {
        return $type === TransactionType::Income ? $absAmount : -$absAmount;
    }
}
