<?php

declare(strict_types=1);

namespace App\Http\Requests\Positions\Concerns;

use App\Enums\InvestKind;
use App\Models\Position;
use App\Support\Isin;
use Illuminate\Validation\Rule;

trait ValidatesPositionByInvestKind
{
    /**
     * @return array<string, mixed>
     */
    protected function securitiesPositionRules(): array
    {
        $account = $this->investAccount();
        $position = $this->positionForUpdate();

        return [
            'isin' => [
                'required',
                'string',
                'size:'.Isin::LENGTH,
                'regex:/^[A-Za-z0-9]{12}$/',
                Rule::unique('positions', 'isin')
                    ->where('account_id', $account->id)
                    ->ignore($position?->id),
            ],
            'market_symbol' => $this->marketSymbolRules(),
            'label' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
            'average_price' => ['required', 'numeric', 'gte:0'],
            'last_price' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function commodityPositionRules(): array
    {
        $account = $this->investAccount();
        $position = $this->positionForUpdate();

        return [
            'isin' => ['prohibited'],
            'market_symbol' => $this->marketSymbolRules(),
            'label' => [
                'required',
                'string',
                'max:255',
                Rule::unique('positions', 'label')
                    ->where('account_id', $account->id)
                    ->ignore($position?->id),
            ],
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,6'],
            'average_price' => ['required', 'numeric', 'gte:0'],
            'last_price' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    protected function accountInvestKind(): InvestKind
    {
        $account = $this->investAccount();
        $kind = $account->invest_kind;

        if ($kind instanceof InvestKind) {
            return $kind;
        }

        if ($kind !== null && $kind !== '') {
            return InvestKind::from((string) $kind);
        }

        return InvestKind::Securities;
    }

    protected function positionForUpdate(): ?Position
    {
        $position = $this->route('position');

        return $position instanceof Position ? $position : null;
    }
}
