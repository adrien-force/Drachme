<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;

class AccountService
{
    /**
     * @param  array{
     *     name: string,
     *     institution?: string|null,
     *     type: AccountType|string,
     *     initial_balance: float|string,
     *     opened_at?: string|null,
     * }  $data
     */
    public function create(User $user, array $data): Account
    {
        $initialBalance = (float) $data['initial_balance'];

        return Account::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'institution' => $data['institution'] ?? null,
            'type' => $data['type'] instanceof AccountType
                ? $data['type']
                : AccountType::from((string) $data['type']),
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
            'currency' => 'EUR',
            'opened_at' => $data['opened_at'] ?? null,
            'is_archived' => false,
        ]);
    }

    /**
     * @param  array{
     *     name: string,
     *     institution?: string|null,
     *     type: AccountType|string,
     *     opened_at?: string|null,
     * }  $data
     */
    public function update(Account $account, array $data): Account
    {
        $account->fill([
            'name' => $data['name'],
            'institution' => $data['institution'] ?? null,
            'type' => $data['type'] instanceof AccountType
                ? $data['type']
                : AccountType::from((string) $data['type']),
            'opened_at' => $data['opened_at'] ?? null,
        ]);

        $account->save();

        return $account;
    }

    public function archive(Account $account): void
    {
        $account->update(['is_archived' => true]);
    }
}
