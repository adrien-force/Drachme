<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use App\Enums\AccountType;
use App\Http\Requests\Accounts\Concerns\ValidatesCreditCardAccount;
use App\Http\Requests\Accounts\Concerns\ValidatesLoanAccount;
use App\Http\Requests\Concerns\ValidatesEntityLogo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    use ValidatesCreditCardAccount;
    use ValidatesLoanAccount;
    use ValidatesEntityLogo;
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'initial_balance' => [
                Rule::requiredIf(fn (): bool => $this->input('type') !== AccountType::Loan->value),
                'nullable',
                'numeric',
            ],
            ...$this->creditCardFieldRules(),
            ...$this->loanFieldRules(),
            ...$this->entityLogoRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->withCreditCardValidator($validator);
        $this->withLoanValidator($validator);
    }
}
