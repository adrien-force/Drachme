<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts\Concerns;

use App\Enums\AccountType;
use Illuminate\Contracts\Validation\Validator;

trait ValidatesLoanAccount
{
    /**
     * @return array<string, mixed>
     */
    protected function loanFieldRules(): array
    {
        return [
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
        ];
    }

    protected function withLoanValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $paymentDay = $this->input('payment_day');

            if ($type !== AccountType::Credit->value && $paymentDay !== null && $paymentDay !== '') {
                $validator->errors()->add(
                    'payment_day',
                    (string) __('ui.accounts.errors.payment_day_loan_only'),
                );
            }
        });
    }
}
