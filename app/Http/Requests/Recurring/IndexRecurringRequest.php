<?php

declare(strict_types=1);

namespace App\Http\Requests\Recurring;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRecurringRequest extends FormRequest
{
    private const PER_PAGE_OPTIONS = [10, 25, 50];

    private const DEFAULT_PER_PAGE = 25;

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
            'search' => ['nullable', 'string', 'max:255'],
            'flow' => ['nullable', Rule::in(['credit', 'debit'])],
            'frequency' => ['nullable', Rule::enum(RecurringFrequency::class)],
            'sort' => ['nullable', Rule::in(['label', 'amount', 'frequency', 'occurrences', 'last_seen'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'confirmed_page' => ['nullable', 'integer', 'min:1'],
            'suggestions_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array{
     *     search: string|null,
     *     flow: string|null,
     *     frequency: string|null,
     *     sort: string,
     *     order: string,
     *     per_page: int,
     *     confirmed_page: int,
     *     suggestions_page: int,
     * }
     */
    public function listFilters(): array
    {
        $search = $this->input('search');
        $flow = $this->input('flow');
        $frequency = $this->input('frequency');
        $sort = $this->input('sort');
        $order = $this->input('order');

        return [
            'search' => is_string($search) && $search !== '' ? $search : null,
            'flow' => is_string($flow) && $flow !== '' ? $flow : null,
            'frequency' => is_string($frequency) && $frequency !== '' ? $frequency : null,
            'sort' => is_string($sort) && $sort !== '' ? $sort : 'amount',
            'order' => is_string($order) && $order === 'asc' ? 'asc' : 'desc',
            'per_page' => $this->resolvedPerPage(),
            'confirmed_page' => max(1, (int) $this->input('confirmed_page', 1)),
            'suggestions_page' => max(1, (int) $this->input('suggestions_page', 1)),
        ];
    }

    /**
     * @return array{
     *     search: string,
     *     flow: string|null,
     *     frequency: string|null,
     *     sort: string,
     *     order: string,
     *     per_page: int,
     *     confirmed_page: int,
     *     suggestions_page: int,
     * }
     */
    public function listFiltersForFrontend(): array
    {
        $filters = $this->listFilters();

        return [
            'search' => $filters['search'] ?? '',
            'flow' => $filters['flow'],
            'frequency' => $filters['frequency'],
            'sort' => $filters['sort'],
            'order' => $filters['order'],
            'per_page' => $filters['per_page'],
            'confirmed_page' => $filters['confirmed_page'],
            'suggestions_page' => $filters['suggestions_page'],
        ];
    }

    public function transactionTypeFilter(): ?TransactionType
    {
        return match ($this->listFilters()['flow']) {
            'debit' => TransactionType::Expense,
            'credit' => TransactionType::Income,
            default => null,
        };
    }

    /**
     * @return list<int>
     */
    public static function perPageOptions(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    private function resolvedPerPage(): int
    {
        $perPage = (int) $this->input('per_page', self::DEFAULT_PER_PAGE);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true)
            ? $perPage
            : self::DEFAULT_PER_PAGE;
    }
}
