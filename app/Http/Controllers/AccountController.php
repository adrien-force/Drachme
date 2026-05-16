<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    use AuthorizesRequests;

    /**
     * Tenant-scoped account detail (SUB-01 probe; full CRUD in SUB-10).
     */
    public function show(Request $request, Account $account): Response
    {
        $this->authorize('view', $account);

        return Inertia::render('shell/placeholder-page', [
            'titleKey' => 'shell.accounts_title',
            'descriptionKey' => 'shell.accounts_description',
        ]);
    }
}
