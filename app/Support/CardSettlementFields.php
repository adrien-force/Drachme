<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AccountType;
use App\Models\Account;

final class CardSettlementFields
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{is_card_settlement: bool, card_period_start: string|null}
     */
    public static function resolveForAccount(Account $account, array $data): array
    {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);

        if ($type !== AccountType::CreditCard) {
            return [
                'is_card_settlement' => false,
                'card_period_start' => null,
            ];
        }

        $isSettlement = filter_var(
            $data['is_card_settlement'] ?? false,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE,
        ) === true;

        if (! $isSettlement) {
            return [
                'is_card_settlement' => false,
                'card_period_start' => null,
            ];
        }

        $rawStart = $data['card_period_start'] ?? null;
        $periodStart = is_string($rawStart) && trim($rawStart) !== ''
            ? trim($rawStart)
            : null;

        return [
            'is_card_settlement' => true,
            'card_period_start' => $periodStart,
        ];
    }
}
