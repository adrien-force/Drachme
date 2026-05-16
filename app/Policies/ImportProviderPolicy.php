<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImportProvider;
use App\Models\User;
use App\Policies\Concerns\HandlesUserOwnedResource;

class ImportProviderPolicy
{
    use HandlesUserOwnedResource;

    public function view(User $user, ImportProvider $importProvider): bool
    {
        return $this->owns($user, $importProvider);
    }

    public function update(User $user, ImportProvider $importProvider): bool
    {
        return $this->owns($user, $importProvider);
    }

    public function delete(User $user, ImportProvider $importProvider): bool
    {
        return $this->owns($user, $importProvider);
    }
}
