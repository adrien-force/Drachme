<?php

declare(strict_types=1);

namespace App\Http\Requests\Positions;

use App\Http\Requests\Positions\Concerns\ValidatesInvestAccount;
use App\Models\Position;
use App\Support\Isin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    use ValidatesInvestAccount;

    public function authorize(): bool
    {
        $position = $this->position();

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
        $account = $this->investAccount();
        $position = $this->position();

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
            'label' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'average_price' => ['required', 'numeric', 'gte:0'],
            'last_price' => ['nullable', 'numeric', 'gte:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('isin')) {
            $this->merge([
                'isin' => Isin::normalize((string) $this->input('isin')),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'isin.regex' => __('ui.positions.validation.isin_format'),
            'isin.unique' => __('ui.positions.validation.isin_unique'),
        ];
    }

    private function position(): ?Position
    {
        $position = $this->route('position');

        return $position instanceof Position ? $position : null;
    }
}
