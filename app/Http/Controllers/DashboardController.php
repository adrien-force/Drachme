<?php

declare(strict_types=1);


namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard/dashboard-index', [
            'kpis' => config('dummy-dashboard.kpis'),
            'netWorthHistory' => config('dummy-dashboard.net_worth_history'),
            'cashflow' => config('dummy-dashboard.cashflow'),
            'isDemoData' => true,
        ]);
    }
}
