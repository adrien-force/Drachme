<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardPresenter;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardPresenter $dashboard,
    ) {}

    public function __invoke(): Response
    {
        $user = Auth::user();
        abort_if($user === null, 403);

        return Inertia::render('dashboard/dashboard-index', $this->dashboard->payload($user));
    }
}
