<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use App\Policies\Concerns\HandlesUserOwnedResource;

class TransactionPolicy
{
    use HandlesUserOwnedResource;

    public function delete(User $user, Transaction $transaction): bool
    {
        return $this->owns($user, $transaction) && $transaction->transfer_pair_id === null;
    }
}
