<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ImportBatch;
use App\Models\User;
use App\Policies\Concerns\HandlesUserOwnedResource;

class ImportBatchPolicy
{
    use HandlesUserOwnedResource;

    public function view(User $user, ImportBatch $importBatch): bool
    {
        return $this->owns($user, $importBatch);
    }

    public function update(User $user, ImportBatch $importBatch): bool
    {
        return $this->owns($user, $importBatch);
    }

    public function delete(User $user, ImportBatch $importBatch): bool
    {
        return $this->owns($user, $importBatch);
    }
}
