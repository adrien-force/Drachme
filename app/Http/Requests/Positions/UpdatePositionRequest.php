<?php

declare(strict_types=1);

namespace App\Http\Requests\Positions;

use App\Enums\InvestKind;
use App\Http\Requests\Positions\Concerns\ValidatesInvestAccount;
use App\Http\Requests\Positions\Concerns\ValidatesPositionByInvestKind;
use App\Http\Requests\Positions\Concerns\ValidatesPositionMarketSymbol;
use App\Models\Position;
use App\Support\Isin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    use ValidatesInvestAccount;
    use ValidatesPositionByInvestKind;
    use ValidatesPositionMarketSymbol;

    public function authorize(): bool
    {
        $position = $this->positionForUpdate();

        return $position !== null
            && $this->user()?->can('update', $position) === true
            && $this->accountIsInvest()
            && $position->account_id === $this->investAccount()->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->accountInvestKind() === InvestKind::Commodities
            ? $this->commodityPositionRules()
            : $this->securitiesPositionRules();
    }

    protected function prepareForValidation(): void
    {
        if ($this->accountInvestKind() !== InvestKind::Commodities && $this->has('isin')) {
            $this->merge([
                'isin' => Isin::normalize((string) $this->input('isin')),
            ]);
        }

        $this->normalizeMarketSymbolInput();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isin.regex' => __('ui.positions.validation.isin_format'),
            'isin.unique' => __('ui.positions.validation.isin_unique'),
            'market_symbol.regex' => __('ui.positions.validation.market_symbol_format'),
            'label.unique' => __('ui.positions.validation.commodity_label_unique'),
        ];
    }
}
