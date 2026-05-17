<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionFormPresenter
{
    public function __construct(
        private readonly AccountService $accounts,
        private readonly CategoryService $categories,
        private readonly CategoryMatcher $categoryMatcher,
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
     * @return array<string, mixed>
     */
    public function serializeTransaction(Transaction $transaction): array
    {
        $account = $transaction->account;
        $type = $transaction->type;
        $date = $transaction->date;

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
