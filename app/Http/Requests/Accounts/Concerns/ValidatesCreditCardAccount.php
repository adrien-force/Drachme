<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts\Concerns;

use App\Enums\AccountType;
use App\Enums\SettlementPeriodMode;
use Illuminate\Validation\Rule;
use App\Models\Account;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesCreditCardAccount
{
    /**
     * @return array<string, mixed>
     */
    protected function creditCardFieldRules(): array
    {
        return [
            'settlement_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'billing_day' => ['nullable', 'integer', 'min:1', 'max:28'],
            'settlement_label_pattern' => ['nullable', 'string', 'max:128'],
            'settlement_period_mode' => [
                'nullable',
                'string',
                Rule::enum(SettlementPeriodMode::class),
            ],
        ];
    }

    protected function withCreditCardValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $isCreditCard = $type === AccountType::CreditCard->value;

            if (! $isCreditCard) {
                return;
            }

            $settlementId = $this->input('settlement_account_id');

            if ($settlementId === null || $settlementId === '') {
                $validator->errors()->add(
                    'settlement_account_id',
                    (string) __('ui.accounts.errors.settlement_required'),
                );

                return;
            }

            $user = $this->user();

            if ($user === null) {
                return;
            }

            $settlement = Account::query()
                ->where('user_id', $user->id)
                ->whereKey((int) $settlementId)
                ->first();

            if ($settlement === null) {
                $validator->errors()->add(
                    'settlement_account_id',
                    (string) __('ui.accounts.errors.settlement_invalid'),
                );

                return;
            }

            if ($settlement->type !== AccountType::Checking) {
                $validator->errors()->add(
                    'settlement_account_id',
                    (string) __('ui.accounts.errors.settlement_must_be_checking'),
                );
            }

            $account = $this->route('account');

            if ($account instanceof Account && (int) $settlementId === $account->id) {
                $validator->errors()->add(
                    'settlement_account_id',
                    (string) __('ui.accounts.errors.settlement_same_account'),
                );
            }
        });
    }
}
