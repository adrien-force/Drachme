<?php

declare(strict_types=1);

namespace App\Http\Requests\Recurring;

use App\Enums\RecurringFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmRecurringRequest extends FormRequest
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
            'label_pattern' => ['required', 'string', 'max:500'],
            'display_label' => ['required', 'string', 'max:500'],
            'expected_amount' => ['required', 'numeric', 'gt:0'],
            'frequency' => ['required', Rule::enum(RecurringFrequency::class)],
            'occurrence_count' => ['required', 'integer', 'min:3'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
        ];
    }

    /**
     * @return array{
     *     label_pattern: string,
     *     display_label: string,
     *     expected_amount: string,
     *     frequency: RecurringFrequency,
     *     occurrence_count: int,
     *     account_id: int|null,
     *     suggested_category_id: int|null,
     * }
     */
    public function suggestionPayload(): array
    {
        return [
            'label_pattern' => (string) $this->input('label_pattern'),
            'display_label' => (string) $this->input('display_label'),
            'expected_amount' => number_format((float) $this->input('expected_amount'), 2, '.', ''),
            'frequency' => RecurringFrequency::from((string) $this->input('frequency')),
            'occurrence_count' => (int) $this->input('occurrence_count'),
            'account_id' => $this->filled('account_id') ? (int) $this->input('account_id') : null,
            'suggested_category_id' => $this->filled('category_id') ? (int) $this->input('category_id') : null,
        ];
    }
}
