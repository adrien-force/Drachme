<?php

declare(strict_types=1);

namespace App\Http\Requests\Transfers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DismissTransferSuggestionRequest extends FormRequest
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
            'outgoing_transaction_id' => [
                'required',
                'integer',
                'different:incoming_transaction_id',
                Rule::exists('transactions', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'incoming_transaction_id' => [
                'required',
                'integer',
                Rule::exists('transactions', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ];
    }
}
