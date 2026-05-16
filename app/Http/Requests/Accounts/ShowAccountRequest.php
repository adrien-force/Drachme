<?php

declare(strict_types=1);

namespace App\Http\Requests\Accounts;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowAccountRequest extends FormRequest
{
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    private const DEFAULT_PER_PAGE = 25;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'chart_all_time' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'flow' => ['nullable', Rule::in(['credit', 'debit'])],
            'sort' => ['nullable', Rule::in(['date', 'label', 'amount', 'type'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function chartAllTime(): bool
    {
        return $this->boolean('chart_all_time');
    }

    public function chartFrom(): ?string
    {
        if ($this->chartAllTime()) {
            return null;
        }

        $from = $this->input('from');

        return is_string($from) && $from !== '' ? $from : null;
    }

    public function chartTo(): ?string
    {
        if ($this->chartAllTime()) {
            return null;
        }

        $to = $this->input('to');

        return is_string($to) && $to !== '' ? $to : null;
    }

    /**
     * @return array{
     *     search: string|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     type: string|null,
     *     flow: string|null,
     *     sort: string,
     *     order: string,
     *     per_page: int,
     *     page: int,
     * }
     */
    public function transactionFilters(): array
    {
        $sort = $this->input('sort');
        $order = $this->input('order');
        $search = $this->input('search');
        $dateFrom = $this->input('date_from');
        $dateTo = $this->input('date_to');
        $type = $this->input('type');
        $flow = $this->input('flow');

        return [
            'search' => is_string($search) && $search !== '' ? $search : null,
            'date_from' => is_string($dateFrom) && $dateFrom !== '' ? $dateFrom : null,
            'date_to' => is_string($dateTo) && $dateTo !== '' ? $dateTo : null,
            'type' => is_string($type) && $type !== '' ? $type : null,
            'flow' => is_string($flow) && $flow !== '' ? $flow : null,
            'sort' => is_string($sort) && $sort !== '' ? $sort : 'date',
            'order' => is_string($order) && $order === 'asc' ? 'asc' : 'desc',
            'per_page' => $this->resolvedPerPage(),
            'page' => max(1, (int) $this->input('page', 1)),
        ];
    }

    /**
     * @return array{
     *     search: string,
     *     date_from: string|null,
     *     date_to: string|null,
     *     type: string|null,
     *     flow: string|null,
     *     sort: string,
     *     order: string,
     *     per_page: int,
     *     page: int,
     * }
     */
    public function transactionFiltersForFrontend(): array
    {
        $filters = $this->transactionFilters();

        return [
            'search' => $filters['search'] ?? '',
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'type' => $filters['type'],
            'flow' => $filters['flow'],
            'sort' => $filters['sort'],
            'order' => $filters['order'],
            'per_page' => $filters['per_page'],
            'page' => $filters['page'],
        ];
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
