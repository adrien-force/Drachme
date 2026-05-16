<?php

declare(strict_types=1);


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
