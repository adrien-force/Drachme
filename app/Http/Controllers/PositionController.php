<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Http\Requests\Positions\StorePositionRequest;
use App\Http\Requests\Positions\UpdatePositionRequest;
use App\Exceptions\MarketDataQuotaExceededException;
use App\Models\Account;
use App\Models\Position;
use App\Services\MarketDataService;
use App\Services\PortfolioSnapshotService;
use App\Services\PositionService;
use App\Services\PositionShowPresenter;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class PositionController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly PositionService $positions,
        private readonly PositionShowPresenter $positionShow,
        private readonly MarketDataService $marketData,
        private readonly PortfolioSnapshotService $portfolioSnapshots,
    ) {}

    public function index(Account $account): Response
    {
        $this->authorize('view', $account);
        $this->abortUnlessInvest($account);

        $positions = $account->positions()
            ->orderBy('label')
            ->get()
            ->map(fn (Position $position): array => $this->serializePosition($position));

        $totalValue = $positions->sum('market_value');

        return Inertia::render('positions/positions-index', [
            'account' => $this->serializeAccount($account),
            'positions' => $positions->values()->all(),
            'totalValue' => $totalValue,
            'portfolioValueSeries' => $this->portfolioSnapshots->portfolioValueSeriesForAccount($account),
            'pageDescription' => __('ui.positions.description', ['account' => $account->name]),
        ]);
    }

    public function show(Position $position): Response
    {
        $this->authorize('view', $position);

        $account = $position->account;

        if ($account === null) {
            abort(404);
        }

        $this->abortUnlessInvest($account);

        return Inertia::render('positions/positions-show', $this->positionShow->present($position));
    }

    public function refreshPrice(Position $position): RedirectResponse
    {
        $this->authorize('view', $position);
        $this->abortUnlessInvestAccount($position);

        try {
            $result = $this->marketData->refreshPosition($position);

            Inertia::flash('toast', [
                'type' => $result === 'updated' ? 'success' : 'warning',
                'message' => $result === 'updated'
                    ? __('ui.positions.market_price_refreshed')
                    : __('ui.positions.market_price_skipped'),
            ]);
        } catch (MarketDataQuotaExceededException) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('ui.investments.market_data_quota'),
            ]);
        } catch (InvalidArgumentException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('ui.investments.market_data_not_configured'),
            ]);
        }

        return to_route('positions.show', $position);
    }

    public function refreshHistory(Position $position): RedirectResponse
    {
        $this->authorize('view', $position);
        $this->abortUnlessInvestAccount($position);

        try {
            $points = $this->marketData->refreshDailyHistoryForPosition($position);

            Inertia::flash('toast', [
                'type' => count($points) > 0 ? 'success' : 'warning',
                'message' => count($points) > 0
                    ? __('ui.positions.market_history_refreshed', ['days' => count($points)])
                    : __('ui.positions.market_history_skipped'),
            ]);
        } catch (MarketDataQuotaExceededException) {
            Inertia::flash('toast', [
                'type' => 'warning',
                'message' => __('ui.investments.market_data_quota'),
            ]);
        } catch (InvalidArgumentException) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('ui.investments.market_data_not_configured'),
            ]);
        }

        return to_route('positions.show', $position);
    }

    public function store(StorePositionRequest $request, Account $account): RedirectResponse
    {
        $user = $request->user();
        if ($user === null) {
            abort(401);
        }

        /** @var array{
         *     isin: string,
         *     label: string,
         *     quantity: float|string,
         *     average_price: float|string,
         *     last_price?: float|string|null,
         *     market_symbol?: string|null,
         * } $data */
        $data = $request->validated();

        try {
            $this->positions->create($user, $account, $data);
        } catch (InvalidArgumentException) {
            abort(403);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.positions.created'),
        ]);

        return to_route('positions.index', $account);
    }

    public function update(UpdatePositionRequest $request, Account $account, Position $position): RedirectResponse
    {
        /** @var array{
         *     isin: string,
         *     label: string,
         *     quantity: float|string,
         *     average_price: float|string,
         *     last_price?: float|string|null,
         *     market_symbol?: string|null,
         * } $data */
        $data = $request->validated();

        try {
            $this->positions->update($position, $data);
        } catch (InvalidArgumentException) {
            abort(403);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.positions.updated'),
        ]);

        return to_route('positions.index', $account);
    }

    public function destroy(Account $account, Position $position): RedirectResponse
    {
        $this->authorize('delete', $position);

        if ($position->account_id !== $account->id) {
            abort(404);
        }

        $this->abortUnlessInvest($account);
        $this->positions->delete($position);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('ui.positions.deleted'),
        ]);

        return to_route('positions.index', $account);
    }

    private function abortUnlessInvest(Account $account): void
    {
        if ($account->type !== AccountType::Invest) {
            abort(403);
        }
    }

    private function abortUnlessInvestAccount(Position $position): void
    {
        $account = $position->account;

        if ($account === null) {
            abort(404);
        }

        $this->abortUnlessInvest($account);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeAccount(Account $account): array
    {
        $type = $account->type;

        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $type instanceof AccountType ? $type->value : (string) $type,
            'currency' => $account->currency,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePosition(Position $position): array
    {
        $lastPriceAt = $position->last_price_at;

        return [
            'id' => $position->id,
            'account_id' => $position->account_id,
            'isin' => $position->isin,
            'market_symbol' => $position->market_symbol,
            'label' => $position->label,
            'quantity' => (float) $position->quantity,
            'average_price' => (float) $position->average_price,
            'last_price' => $position->last_price !== null ? (float) $position->last_price : null,
            'last_price_at' => $lastPriceAt instanceof \DateTimeInterface
                ? $lastPriceAt->format('Y-m-d')
                : null,
            'unit_price' => $this->positions->unitPrice($position),
            'market_value' => $this->positions->marketValue($position),
            'uses_average_price' => $this->positions->usesAveragePrice($position),
        ];
    }
}
