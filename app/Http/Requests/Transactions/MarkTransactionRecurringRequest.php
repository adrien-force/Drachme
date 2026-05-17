<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Enums\RecurringFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkTransactionRecurringRequest extends FormRequest
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
        return [
            'frequency' => ['required', Rule::enum(RecurringFrequency::class)],
        ];
    }

    public function frequency(): RecurringFrequency
    {
        return RecurringFrequency::from((string) $this->input('frequency'));
    }
}
