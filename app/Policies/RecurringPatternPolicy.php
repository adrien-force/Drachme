<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RecurringPattern;
use App\Models\User;
use App\Policies\Concerns\HandlesUserOwnedResource;

class RecurringPatternPolicy
{
    use HandlesUserOwnedResource;

    public function delete(User $user, RecurringPattern $recurringPattern): bool
    {
        return $this->owns($user, $recurringPattern);
    }
}
