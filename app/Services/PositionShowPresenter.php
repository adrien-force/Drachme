<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;

class PositionShowPresenter
{
    public function __construct(
        private readonly PositionService $positions,
        private readonly PositionSnapshotMovementService $snapshotMovements,
        private readonly MarketDataService $marketData,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(Position $position): array
    {
        $position->loadMissing('account');
        $account = $position->account;

        if ($account === null) {
            throw new \InvalidArgumentException('position_account_missing');
        }

        $lastPriceAt = $position->last_price_at;
        return [
            'position' => [
                'id' => $position->id,
                'account_id' => $position->account_id,
                'isin' => $position->isin,
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
                'market_symbol' => $position->market_symbol,
            ],
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'currency' => $account->currency,
            ],
            'inferredMovements' => $this->snapshotMovements->inferredMovementsForPosition($position),
            'portfolioValueSeries' => $this->snapshotMovements->snapshotPortfolioValueSeriesForPosition($position),
            'marketPriceSeries' => $this->marketData->cachedDailyHistoryForPosition($position),
            'marketDataConfigured' => MarketDataService::isConfigured(),
        ];
    }
}
