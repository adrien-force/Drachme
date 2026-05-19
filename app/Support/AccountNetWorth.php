<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AccountType;

final class AccountNetWorth
{
    /**
     * Credit card accounts hold purchase detail only; settlement lives on checking.
     */
    public static function countsTowardNetWorth(AccountType $type): bool
    {
        return $type !== AccountType::CreditCard;
    }

    /**
     * Positive amount owed on a credit card (expenses accumulate as negative balance).
     */
    public static function creditCardAmountOwed(float $balance): float
    {
        return abs(min(0.0, $balance));
    }

    /**
     * @return 'asset'|'liability'
     */
    public static function balanceBucket(AccountType $type, float $balance): string
    {
        if ($type === AccountType::Credit || $type === AccountType::Loan) {
            return 'liability';
        }

        if ($type === AccountType::CreditCard) {
            return 'liability';
        }

        return $balance < 0 ? 'liability' : 'asset';
    }

    public static function liabilityAmount(AccountType $type, float $balance): float
    {
        if ($type === AccountType::Credit || $type === AccountType::Loan) {
            return max(0.0, $balance);
        }

        if ($type === AccountType::CreditCard) {
            return self::creditCardAmountOwed($balance);
        }

        return abs(min(0.0, $balance));
    }
}
