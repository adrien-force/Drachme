<?php

declare(strict_types=1);


namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesAuthenticatedUser
{
    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
    }
}
