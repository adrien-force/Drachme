<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryRuleFlow: string
{
    case Credit = 'credit';
    case Debit = 'debit';

    public static function fromAmount(float|string|null $amount): ?self
    {
        if ($amount === null) {
            return null;
        }

        $value = (float) $amount;

        if ($value > 0) {
            return self::Credit;
        }

        if ($value < 0) {
            return self::Debit;
        }

        return null;
    }

    public function matchesAmount(float|string $amount): bool
    {
        $value = (float) $amount;

        return match ($this) {
            self::Credit => $value > 0,
            self::Debit => $value < 0,
        };
    }
}
