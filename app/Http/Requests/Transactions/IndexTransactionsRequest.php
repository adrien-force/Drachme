<?php

declare(strict_types=1);

namespace App\Http\Requests\Transactions;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTransactionsRequest extends FormRequest
{
    private const PER_PAGE_OPTIONS = [25, 50, 100];

    private const DEFAULT_PER_PAGE = 50;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'flow' => ['nullable', Rule::in(['credit', 'debit'])],
            'category_id' => ['nullable', 'string', 'max:64'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('user_id', $userId)),
            ],
            'amount_min' => ['nullable', 'numeric'],
            'amount_max' => ['nullable', 'numeric'],
            'sort' => ['nullable', Rule::in(['date', 'label', 'amount', 'type', 'category', 'account'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'edit_transaction' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function editTransactionId(): ?int
    {
        $id = $this->integer('edit_transaction');

        return $id > 0 ? $id : null;
    }

    /**
     * @return array{
     *     search: string|null,
     *     date_from: string|null,
     *     date_to: string|null,
     *     type: string|null,
     *     flow: string|null,
     *     category_id: string|null,
     *     account_id: int|null,
     *     amount_min: float|null,
     *     amount_max: float|null,
     *     sort: string,
     *     order: string,
     *     per_page: int,
     *     page: int,
     * }
     */
    public function listFilters(): array
    {
        $sort = $this->input('sort');
        $order = $this->input('order');
        $search = $this->input('search');
        $dateFrom = $this->input('date_from');
        $dateTo = $this->input('date_to');
        $type = $this->input('type');
        $flow = $this->input('flow');
        $categoryId = $this->input('category_id');
        $amountMin = $this->input('amount_min');
        $amountMax = $this->input('amount_max');
        $accountId = $this->integer('account_id');

        return [
            'search' => is_string($search) && $search !== '' ? $search : null,
            'date_from' => is_string($dateFrom) && $dateFrom !== '' ? $dateFrom : null,
            'date_to' => is_string($dateTo) && $dateTo !== '' ? $dateTo : null,
            'type' => is_string($type) && $type !== '' ? $type : null,
            'flow' => is_string($flow) && $flow !== '' ? $flow : null,
            'category_id' => is_string($categoryId) && $categoryId !== '' ? $categoryId : null,
            'account_id' => $accountId > 0 ? $accountId : null,
            'amount_min' => is_numeric($amountMin) ? (float) $amountMin : null,
            'amount_max' => is_numeric($amountMax) ? (float) $amountMax : null,
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
     *     category_id: string|null,
     *     account_id: int|null,
     *     amount_min: string|null,
     *     amount_max: string|null,
     *     sort: string,
     *     order: string,
     *     per_page: int,
     *     page: int,
     * }
     */
    public function listFiltersForFrontend(): array
    {
        $filters = $this->listFilters();

        return [
            'search' => $filters['search'] ?? '',
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'type' => $filters['type'],
            'flow' => $filters['flow'],
            'category_id' => $filters['category_id'],
            'account_id' => $filters['account_id'],
            'amount_min' => $filters['amount_min'] !== null ? (string) $filters['amount_min'] : null,
            'amount_max' => $filters['amount_max'] !== null ? (string) $filters['amount_max'] : null,
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
