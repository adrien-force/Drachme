<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PortfolioSnapshot;
use App\Models\User;
use App\Policies\Concerns\HandlesUserOwnedResource;

class PortfolioSnapshotPolicy
{
    use HandlesUserOwnedResource;

    public function delete(User $user, PortfolioSnapshot $portfolioSnapshot): bool
    {
        return $this->owns($user, $portfolioSnapshot);
    }
}
