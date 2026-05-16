<?php

declare(strict_types=1);


namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use App\Policies\Concerns\HandlesUserOwnedResource;

class AccountPolicy
{
    use HandlesUserOwnedResource;

    public function view(User $user, Account $account): bool
    {
        return $this->owns($user, $account);
    }

    public function update(User $user, Account $account): bool
    {
        return $this->owns($user, $account);
    }

    public function delete(User $user, Account $account): bool
    {
        return $this->owns($user, $account);
    }
}
