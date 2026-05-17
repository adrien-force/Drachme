<?php

declare(strict_types=1);

namespace App\Http\Requests\Transfers;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AcceptTransferSuggestionRequest extends FormRequest
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

    public function outgoingTransaction(): Transaction
    {
        return Transaction::query()->findOrFail((int) $this->input('outgoing_transaction_id'));
    }

    public function incomingTransaction(): Transaction
    {
        return Transaction::query()->findOrFail((int) $this->input('incoming_transaction_id'));
    }
}
