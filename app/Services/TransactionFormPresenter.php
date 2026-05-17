<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class TransactionFormPresenter
{
    public function __construct(
        private readonly AccountService $accounts,
        private readonly CategoryService $categories,
        private readonly CategoryMatcher $categoryMatcher,
        private readonly RecurringPatternService $recurringPatterns,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(?Transaction $transaction, ?Account $presetAccount): array
    {
        $user = Auth::user();
        if ($user !== null) {
            $this->categories->seedDefaultsForUser($user);
        }

        $accountOptions = Account::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'logo_path'])
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'logo_url' => $this->accounts->logoUrl($account),
            ])
            ->values()
            ->all();

        $suggestedCategory = null;
        if ($user !== null && $transaction !== null && $transaction->category_id === null) {
            $matched = $this->categoryMatcher->match($user, $transaction->label);
            if ($matched !== null) {
                $suggestedCategory = [
                    'id' => $matched->id,
                    'name' => $matched->name,
                    'color' => $matched->color,
                ];
            }
        }

        return [
            'transaction' => $transaction !== null ? $this->serializeTransaction($transaction) : null,
            'accounts' => $accountOptions,
            'presetAccountId' => $presetAccount?->id,
            'typeOptions' => $this->typeOptions(),
            'categoryOptions' => $user !== null ? $this->categories->flatSelectOptions($user) : [],
            'suggestedCategory' => $suggestedCategory,
        ];
    }

    /**
     * @param  LengthAwarePaginator<int, Transaction>  $paginator
     *
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{
     *         current_page: int,
     *         last_page: int,
     *         per_page: int,
     *         total: int,
     *         from: int|null,
     *         to: int|null,
     *     },
     * }
     */
    public function serializePaginator(LengthAwarePaginator $paginator): array
    {
        $items = collect($paginator->items());
        $user = $items->first()?->user;
        $recurringIndexed = $user instanceof User
            ? $this->recurringPatterns->confirmedPatternsIndexed($user)
            : [];

        return [
            'data' => array_values(
                $items
                    ->map(fn (Transaction $transaction): array => $this->serializeTransaction(
                        $transaction,
                        $recurringIndexed,
                    ))
                    ->all(),
            ),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    /**
     * @param  array<string, \App\Models\RecurringPattern>  $recurringIndexed
     *
     * @return array<string, mixed>
     */
    public function serializeTransaction(Transaction $transaction, array $recurringIndexed = []): array
    {
        $account = $transaction->account;
        $type = $transaction->type;
        $date = $transaction->date;

        $recurringMatch = $recurringIndexed !== []
            ? $this->recurringPatterns->matchIndexed($transaction, $recurringIndexed)
            : null;

        if ($recurringMatch === null && $transaction->user !== null && $recurringIndexed === []) {
            $recurringMatch = $this->recurringPatterns->matchesConfirmedPattern(
                $transaction->user,
                $transaction,
            );
        }

        return [
            'id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'account_name' => $account?->name,
            'account_logo_url' => $account !== null ? $this->accounts->logoUrl($account) : null,
            'date' => $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : (string) $date,
            'label' => $transaction->label,
            'amount' => (float) $transaction->amount,
            'type' => $type instanceof TransactionType ? $type->value : (string) $type,
            'notes' => $transaction->notes,
            'is_transfer_linked' => $transaction->transfer_pair_id !== null,
            'import_batch_id' => $transaction->import_batch_id,
            'category_id' => $transaction->category_id,
            'category_name' => $transaction->category?->name,
            'category_color' => $transaction->category?->color,
            'recurring_pattern_id' => $recurringMatch?->id,
            'recurring_display_label' => $recurringMatch?->display_label,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return array_values(array_map(
            static fn (TransactionType $type): array => [
                'value' => $type->value,
                'label' => (string) __("ui.transactions.types.{$type->value}"),
            ],
            TransactionType::cases(),
        ));
    }
}
