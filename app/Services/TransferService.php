<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Events\TransactionChanged;
use App\Models\Account;
use App\Models\DismissedTransferSuggestion;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransferService
{
    public function linkPair(User $user, Transaction $first, Transaction $second): void
    {
        $this->assertLinkablePair($user, $first, $second);

        DB::transaction(function () use ($first, $second): void {
            $first->update([
                'transfer_pair_id' => $second->id,
                'type' => TransactionType::Transfer,
            ]);
            $second->update([
                'transfer_pair_id' => $first->id,
                'type' => TransactionType::Transfer,
            ]);
        });

        $this->dispatchBalanceRefresh($first, $second);
    }

    public function dismissPair(User $user, Transaction $first, Transaction $second): void
    {
        $this->assertOwnedByUser($user, $first, $second);

        [$transactionAId, $transactionBId] = DismissedTransferSuggestion::canonicalPairIds(
            $first->id,
            $second->id,
        );

        DismissedTransferSuggestion::query()->firstOrCreate([
            'user_id' => $user->id,
            'transaction_a_id' => $transactionAId,
            'transaction_b_id' => $transactionBId,
        ]);
    }

    /**
     * @param  array{
     *     from_account_id: int,
     *     to_account_id: int,
     *     date: string,
     *     label: string,
     *     amount: float|string,
     *     notes?: string|null,
     * }  $data
     *
     * @return array{0: Transaction, 1: Transaction}
     */
    public function createManualPair(User $user, array $data): array
    {
        if ($data['from_account_id'] === $data['to_account_id']) {
            throw new InvalidArgumentException('transfer_same_account');
        }

        $fromAccount = $this->resolveAccount($user, (int) $data['from_account_id']);
        $toAccount = $this->resolveAccount($user, (int) $data['to_account_id']);
        $amount = $this->parsePositiveAmount($data['amount']);
        $formatted = number_format($amount, 2, '.', '');

        return DB::transaction(function () use ($user, $data, $fromAccount, $toAccount, $formatted): array {
            $outgoing = Transaction::query()->create([
                'user_id' => $user->id,
                'account_id' => $fromAccount->id,
                'date' => $data['date'],
                'label' => $data['label'],
                'amount' => '-'.$formatted,
                'type' => TransactionType::Transfer,
                'notes' => $data['notes'] ?? null,
            ]);

            $incoming = Transaction::query()->create([
                'user_id' => $user->id,
                'account_id' => $toAccount->id,
                'date' => $data['date'],
                'label' => $data['label'],
                'amount' => $formatted,
                'type' => TransactionType::Transfer,
                'notes' => $data['notes'] ?? null,
            ]);

            $outgoing->update(['transfer_pair_id' => $incoming->id]);
            $incoming->update(['transfer_pair_id' => $outgoing->id]);

            TransactionChanged::dispatch($fromAccount);
            TransactionChanged::dispatch($toAccount);

            return [$outgoing->fresh() ?? $outgoing, $incoming->fresh() ?? $incoming];
        });
    }

    private function assertLinkablePair(User $user, Transaction $first, Transaction $second): void
    {
        $this->assertOwnedByUser($user, $first, $second);

        if ($first->id === $second->id) {
            throw new InvalidArgumentException('transfer_same_transaction');
        }

        if ($first->transfer_pair_id !== null || $second->transfer_pair_id !== null) {
            throw new InvalidArgumentException('transfer_already_linked');
        }

        if ($first->account_id === $second->account_id) {
            throw new InvalidArgumentException('transfer_same_account');
        }

        $firstAmount = (float) $first->amount;
        $secondAmount = (float) $second->amount;

        if (($firstAmount > 0 && $secondAmount > 0) || ($firstAmount < 0 && $secondAmount < 0)) {
            throw new InvalidArgumentException('transfer_amount_mismatch');
        }

        if (abs(abs($firstAmount) - abs($secondAmount)) >= 0.001) {
            throw new InvalidArgumentException('transfer_amount_mismatch');
        }
    }

    private function assertOwnedByUser(User $user, Transaction $first, Transaction $second): void
    {
        if ($first->user_id !== $user->id || $second->user_id !== $user->id) {
            throw new InvalidArgumentException('transfer_forbidden');
        }
    }

    private function resolveAccount(User $user, int $accountId): Account
    {
        $account = Account::query()
            ->whereKey($accountId)
            ->where('user_id', $user->id)
            ->first();

        if ($account === null) {
            throw new InvalidArgumentException('transfer_account_forbidden');
        }

        return $account;
    }

    private function parsePositiveAmount(float|string $amount): float
    {
        $value = abs((float) $amount);

        if ($value < 0.001) {
            throw new InvalidArgumentException('transfer_amount_zero');
        }

        return $value;
    }

    private function dispatchBalanceRefresh(Transaction $first, Transaction $second): void
    {
        $first->loadMissing('account');
        $second->loadMissing('account');

        if ($first->account !== null) {
            TransactionChanged::dispatch($first->account);
        }

        if ($second->account !== null && $second->account_id !== $first->account_id) {
            TransactionChanged::dispatch($second->account);
        }
    }
}
