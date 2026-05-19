<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts\Concerns;

use App\Enums\AccountType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait ValidatesLoanAccount
{
    /**
     * @return array<string, mixed>
     */
    protected function loanFieldRules(): array
    {
        $isLoan = fn (): bool => $this->input('type') === AccountType::Loan->value;

        return [
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'loan_original_principal' => [
                Rule::requiredIf($isLoan),
                'nullable',
                'numeric',
                'gt:0',
            ],
            'loan_interest_rate' => [
                Rule::requiredIf($isLoan),
                'nullable',
                'numeric',
                'gte:0',
                'lte:100',
            ],
            'loan_end_date' => [
                Rule::requiredIf($isLoan),
                'nullable',
                'date',
                'after:opened_at',
            ],
            'opened_at' => [
                Rule::requiredIf($isLoan),
                'nullable',
                'date',
            ],
        ];
    }

    protected function withLoanValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $loanOnlyFields = [
                'payment_day',
                'loan_original_principal',
                'loan_interest_rate',
                'loan_end_date',
            ];

            foreach ($loanOnlyFields as $field) {
                $value = $this->input($field);

                if ($type !== AccountType::Loan->value && $value !== null && $value !== '') {
                    $validator->errors()->add(
                        $field,
                        (string) __('ui.accounts.errors.loan_fields_loan_only'),
                    );
                }
            }

            if ($type !== AccountType::Loan->value) {
                return;
            }

            if ($this->input('initial_balance') !== null
                && $this->input('initial_balance') !== ''
                && $this->input('loan_original_principal') !== null
                && $this->input('loan_original_principal') !== ''
                && (float) $this->input('initial_balance') !== (float) $this->input('loan_original_principal')) {
                $validator->errors()->add(
                    'loan_original_principal',
                    (string) __('ui.accounts.errors.loan_use_principal_only'),
                );
            }
        });
    }
}
