<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Services\AccountService;
use App\Services\PortfolioSnapshotService;
use App\Services\PositionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvestmentsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AccountService $accounts,
        private readonly PositionService $positions,
        private readonly PortfolioSnapshotService $portfolioSnapshots,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Account::class);

        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        $investAccounts = Account::query()
            ->active()
            ->where('type', AccountType::Invest)
            ->withCount('positions')
            ->orderBy('name')
            ->get();

        $portfolioRows = $investAccounts->map(function (Account $account) use ($user): array {
            $positions = Position::query()
                ->where('account_id', $account->id)
                ->where('user_id', $user->id)
                ->get();

            $totalValue = $positions->sum(
                fn (Position $position): float => $this->positions->marketValue($position),
            );

            return [
                'id' => $account->id,
                'name' => $account->name,
                'logo_url' => $this->accounts->logoUrl($account),
                'institution' => $account->institution,
                'currency' => $account->currency,
                'current_balance' => (float) $account->current_balance,
                'positions_count' => $account->positions_count ?? $positions->count(),
                'positions_value' => $totalValue,
                'import_history' => $this->portfolioSnapshots->detailedHistoryForAccount(
                    $user,
                    $account->id,
                ),
            ];
        });

        return Inertia::render('investments/investments-index', [
            'accounts' => $portfolioRows->values()->all(),
        ]);
    }

    public function destroySnapshot(PortfolioSnapshot $portfolioSnapshot): RedirectResponse
    {
        $this->authorize('delete', $portfolioSnapshot);

        $user = request()->user();
        if ($user === null) {
            abort(401);
        }

        $this->portfolioSnapshots->deleteSnapshot($user, $portfolioSnapshot);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.investments.import_deleted'),
        ]);

        return to_route('investments.index');
    }
}
