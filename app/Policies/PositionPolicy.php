<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Position;
use App\Policies\Concerns\HandlesUserOwnedResource;

class PositionPolicy
{
    use HandlesUserOwnedResource;
}
