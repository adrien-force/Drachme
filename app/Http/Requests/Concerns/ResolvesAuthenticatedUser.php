<?php

namespace App\Http\Requests\Concerns;

use App\Models\User;

trait ResolvesAuthenticatedUser
{
    protected function authenticatedUser(): User
    {
        $user = $this->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
