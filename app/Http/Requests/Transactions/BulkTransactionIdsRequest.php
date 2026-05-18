<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkTransactionIdsRequest extends FormRequest
{
    public const int MAX_IDS = 200;

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Transaction::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'transaction_ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
            'transaction_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('transactions', 'id')->where(
                    fn ($query) => $query->where('user_id', $userId),
                ),
            ],
        ];
    }

    /**
     * @return list<int>
     */
    public function transactionIds(): array
    {
        /** @var list<int|string> $ids */
        $ids = $this->input('transaction_ids', []);

        return array_values(array_map(intval(...), $ids));
    }
}
