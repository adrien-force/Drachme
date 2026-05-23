<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionLabelToken;
use App\Support\TransactionLabelIndex;
use Illuminate\Support\Facades\DB;
use ParagonIE\CipherSweet\Exception\CipherSweetException;

final class TransactionLabelTokenService
{
    public function __construct(
        private readonly TransactionLabelIndex $labelIndex,
    ) {}

    public function syncForTransaction(Transaction $transaction): void
    {
        if ($transaction->getKey() === null) {
            return;
        }

        $label = $transaction->label;
        if (! is_string($label) || $label === '') {
            $transaction->labelTokens()->delete();

            return;
        }

        try {
            $hashes = [];
            foreach ($this->labelIndex->tokenizeLabel($label) as $token) {
                $hashes[] = $this->labelIndex->hashToken($token);
            }
        } catch (CipherSweetException) {
            return;
        }

        $hashes = array_values(array_unique($hashes));

        DB::transaction(function () use ($transaction, $hashes): void {
            $transaction->labelTokens()->delete();

            foreach ($hashes as $hash) {
                TransactionLabelToken::query()->create([
                    'transaction_id' => $transaction->getKey(),
                    'token_hash' => $hash,
                ]);
            }
        });
    }

    public function reindexAll(): int
    {
        $count = 0;

        Transaction::query()->orderBy('id')->chunkById(200, function ($transactions) use (&$count): void {
                foreach ($transactions as $transaction) {
                    $this->syncForTransaction($transaction);
                    $count++;
                }
            });

        return $count;
    }
}
