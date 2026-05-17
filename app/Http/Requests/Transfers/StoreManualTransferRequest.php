<?php

declare(strict_types=1);

namespace App\Http\Requests\Transfers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'from_account_id' => [
                'required',
                'integer',
                'different:to_account_id',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'to_account_id' => [
                'required',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'date' => ['required', 'date'],
            'label' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array{
     *     from_account_id: int,
     *     to_account_id: int,
     *     date: string,
     *     label: string,
     *     amount: float|string,
     *     notes?: string|null,
     * }
     */
    public function transferData(): array
    {
        return [
            'from_account_id' => (int) $this->input('from_account_id'),
            'to_account_id' => (int) $this->input('to_account_id'),
            'date' => (string) $this->input('date'),
            'label' => (string) $this->input('label'),
            'amount' => $this->input('amount'),
            'notes' => $this->filled('notes') ? (string) $this->input('notes') : null,
        ];
    }
}
