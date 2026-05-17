<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardPresenter;
use App\Support\BillingPeriod;
use App\Support\DashboardDateRange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardPresenter $dashboard,
    ) {}

    public function __invoke(Request $request): Response
    {
        $user = Auth::user();
        abort_if($user === null, 403);

        $monthStartDay = BillingPeriod::normalizeStartDay((int) ($user->month_start_day ?? 1));
        $range = DashboardDateRange::fromRequest($request, $monthStartDay, $user);

        return Inertia::render(
            'dashboard/dashboard-index',
            $this->dashboard->payload($user, $range),
        );
    }
}
