<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions\Concerns;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesCardSettlementFields
{
    /**
     * @return array<string, mixed>
     */
    protected function cardSettlementFieldRules(): array
    {
        return [
            'is_card_settlement' => ['nullable', 'boolean'],
            'card_period_start' => ['nullable', 'date'],
        ];
    }

    protected function withCardSettlementValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $accountId = $this->input('account_id');

            if ($accountId === null || $accountId === '') {
                return;
            }

            $user = $this->user();

            if ($user === null) {
                return;
            }

            $account = Account::query()
                ->where('user_id', $user->id)
                ->whereKey((int) $accountId)
                ->first();

            if ($account === null) {
                return;
            }

            $type = $account->type instanceof AccountType
                ? $account->type
                : AccountType::from((string) $account->type);

            $wantsSettlement = filter_var(
                $this->input('is_card_settlement'),
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE,
            ) === true;

            if ($type !== AccountType::CreditCard) {
                if ($wantsSettlement || $this->filled('card_period_start')) {
                    $validator->errors()->add(
                        'is_card_settlement',
                        (string) __('ui.transactions.errors.card_settlement_wrong_account'),
                    );
                }

                return;
            }

            if ($wantsSettlement && (float) $this->input('amount') <= 0) {
                $validator->errors()->add(
                    'is_card_settlement',
                    (string) __('ui.transactions.errors.card_settlement_positive_amount'),
                );
            }

            $periodStart = $this->input('card_period_start');
            $settlementDate = $this->input('date');

            if (
                $wantsSettlement
                && is_string($periodStart)
                && $periodStart !== ''
                && is_string($settlementDate)
                && $settlementDate !== ''
                && $periodStart > $settlementDate
            ) {
                $validator->errors()->add(
                    'card_period_start',
                    (string) __('ui.transactions.errors.card_period_start_after_settlement'),
                );
            }
        });
    }
}
