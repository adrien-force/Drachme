<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\TransferSuggestion;
use App\Models\Account;
use App\Models\Transaction;
use Carbon\CarbonImmutable;

class TransferPresenter
{
    public function __construct(
        private readonly AccountService $accounts,
    ) {}

    /**
     * @param  list<TransferSuggestion>  $suggestions
     *
     * @return list<array<string, mixed>>
     */
    public function serializeSuggestions(array $suggestions): array
    {
        return array_map(
            fn (TransferSuggestion $suggestion): array => $this->serializeSuggestion($suggestion),
            $suggestions,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSuggestion(TransferSuggestion $suggestion): array
    {
        return [
            'outgoing' => $this->serializeTransaction($suggestion->outgoing),
            'incoming' => $this->serializeTransaction($suggestion->incoming),
            'score' => $suggestion->score,
        ];
    }

    /**
     * @return list<array{id: int, name: string, logo_url: string|null}>
     */
    public function accountOptions(): array
    {
        $options = Account::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'logo_path'])
            ->map(fn (Account $account): array => [
                'id' => $account->id,
                'name' => $account->name,
                'logo_url' => $this->accounts->logoUrl($account),
            ])
            ->all();

        return array_values($options);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTransaction(Transaction $transaction): array
    {
        $transaction->loadMissing('account:id,name,logo_path');

        return [
            'id' => $transaction->id,
            'account_id' => $transaction->account_id,
            'account_name' => $transaction->account->name ?? '',
            'account_logo_url' => $transaction->account !== null
                ? $this->accounts->logoUrl($transaction->account)
                : null,
            'date' => CarbonImmutable::parse($transaction->date)->toDateString(),
            'label' => $transaction->label,
            'amount' => $transaction->amount,
        ];
    }
}
