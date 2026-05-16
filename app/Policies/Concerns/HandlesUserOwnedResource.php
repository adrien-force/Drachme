<?php

declare(strict_types=1);


namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait HandlesUserOwnedResource
{
    protected function owns(User $user, Model $model): bool
    {
        return isset($model->user_id) && (int) $model->user_id === (int) $user->id;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }
}
