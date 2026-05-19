<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts\Concerns;

use App\Enums\AccountType;
use App\Enums\InvestKind;
use App\Models\Account;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

trait ValidatesInvestKind
{
    /**
     * @return array<string, mixed>
     */
    protected function investKindFieldRules(): array
    {
        return [
            'invest_kind' => [
                'nullable',
                Rule::enum(InvestKind::class),
                Rule::requiredIf(fn (): bool => $this->input('type') === AccountType::Invest->value),
            ],
        ];
    }

    protected function withInvestKindValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') !== AccountType::Invest->value) {
                return;
            }

            $account = $this->route('account');
            if (! $account instanceof Account) {
                return;
            }

            $newKind = $this->input('invest_kind');
            if ($newKind === null || $newKind === '') {
                return;
            }

            $current = $account->invest_kind instanceof InvestKind
                ? $account->invest_kind->value
                : (string) ($account->invest_kind ?? InvestKind::Securities->value);

            if ($newKind === $current) {
                return;
            }

            if ($account->positions()->exists()) {
                $validator->errors()->add(
                    'invest_kind',
                    __('ui.accounts.validation.invest_kind_locked'),
                );
            }
        });
    }
}
