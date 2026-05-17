<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\HandlesUserOwnedResource;

class CategoryPolicy
{
    use HandlesUserOwnedResource;

    public function delete(User $user, Category $category): bool
    {
        return $this->owns($user, $category) && ! $category->isUncategorized();
    }
}
