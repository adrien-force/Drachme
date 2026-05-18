<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PositionInferredMovementSide;
use App\Models\PortfolioSnapshot;
use App\Models\Position;
use App\Support\Isin;
use Carbon\CarbonImmutable;

class PositionSnapshotMovementService
{
    private const QUANTITY_EPSILON = 0.000001;

    /**
     * Inferred buy/sell events from consecutive portfolio import snapshots.
     *
     * @return list<array{
     *     snapshot_id: int,
     *     imported_at: string,
     *     side: string,
     *     quantity: float,
     *     quantity_before: float,
     *     quantity_after: float,
     *     unit_price: float|null,
     *     inferred: true,
     * }>
     */
    public function inferredMovementsForPosition(Position $position): array
    {
        $isin = Isin::normalize($position->isin);
        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $position->user_id)
            ->where('account_id', $position->account_id)
            ->orderBy('imported_at')
            ->orderBy('id')
            ->get();

        $previousQuantity = null;
        /** @var array<string, mixed>|null $previousLine */
        $previousLine = null;
        $movements = [];

        foreach ($snapshots as $snapshot) {
            $line = $this->findLine($snapshot->lines, $isin);
            $currentQuantity = $line !== null ? (float) ($line['quantity'] ?? 0) : null;
            $importedAt = $this->resolveImportedAt($snapshot->imported_at);

            if ($previousQuantity === null && $currentQuantity !== null && $currentQuantity > self::QUANTITY_EPSILON) {
                $movements[] = $this->movement(
                    $snapshot,
                    PositionInferredMovementSide::Buy,
                    $currentQuantity,
                    0.0,
                    $currentQuantity,
                    $this->unitPriceFromLine($line),
                    $importedAt,
                );
            } elseif ($previousQuantity !== null && $currentQuantity === null) {
                $movements[] = $this->movement(
                    $snapshot,
                    PositionInferredMovementSide::Sell,
                    $previousQuantity,
                    $previousQuantity,
                    0.0,
                    $this->unitPriceFromLine($previousLine),
                    $importedAt,
                );
            } elseif ($previousQuantity !== null && $currentQuantity !== null) {
                $delta = $currentQuantity - $previousQuantity;

                if ($delta > self::QUANTITY_EPSILON) {
                    $movements[] = $this->movement(
                        $snapshot,
                        PositionInferredMovementSide::Buy,
                        $delta,
                        $previousQuantity,
                        $currentQuantity,
                        $this->unitPriceFromLine($line),
                        $importedAt,
                    );
                } elseif ($delta < -self::QUANTITY_EPSILON) {
                    $movements[] = $this->movement(
                        $snapshot,
                        PositionInferredMovementSide::Sell,
                        abs($delta),
                        $previousQuantity,
                        $currentQuantity,
                        $this->unitPriceFromLine($line),
                        $importedAt,
                    );
                }
            }

            $previousQuantity = $currentQuantity;
            $previousLine = $line;
        }

        return $movements;
    }

    /**
     * Position market value at each import snapshot (quantity × price, or imported market_value).
     *
     * @return list<array{ date: string, value: float }>
     */
    public function snapshotPortfolioValueSeriesForPosition(Position $position): array
    {
        $isin = Isin::normalize($position->isin);
        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $position->user_id)
            ->where('account_id', $position->account_id)
            ->orderBy('imported_at')
            ->orderBy('id')
            ->get();

        $points = [];

        foreach ($snapshots as $snapshot) {
            $line = $this->findLine($snapshot->lines, $isin);

            if ($line === null) {
                continue;
            }

            $value = $this->portfolioValueFromLine($line);

            if ($value === null) {
                continue;
            }

            $importedAt = $this->resolveImportedAt($snapshot->imported_at);

            $points[] = [
                'date' => $importedAt->toDateString(),
                'value' => $value,
            ];
        }

        return $points;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<string, mixed>|null
     */
    private function findLine(array $lines, string $isin): ?array
    {
        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            if (Isin::normalize((string) ($line['isin'] ?? '')) === $isin) {
                return $line;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function portfolioValueFromLine(array $line): ?float
    {
        $marketValue = $line['market_value'] ?? null;

        if (is_string($marketValue) || is_numeric($marketValue)) {
            $parsed = (float) $marketValue;

            if ($parsed > self::QUANTITY_EPSILON) {
                return $parsed;
            }
        }

        $quantity = (float) ($line['quantity'] ?? 0);

        if ($quantity <= self::QUANTITY_EPSILON) {
            return null;
        }

        $unitPrice = $this->unitPriceFromLine($line);

        if ($unitPrice === null) {
            return null;
        }

        return $quantity * $unitPrice;
    }

    /**
     * @param  array<string, mixed>|null  $line
     */
    private function unitPriceFromLine(?array $line): ?float
    {
        if ($line === null) {
            return null;
        }

        if (array_key_exists('last_price', $line) && $line['last_price'] !== null) {
            return (float) $line['last_price'];
        }

        $average = (float) ($line['average_price'] ?? 0);

        return $average > 0 ? $average : null;
    }

    /**
     * @return array{
     *     snapshot_id: int,
     *     imported_at: string,
     *     side: string,
     *     quantity: float,
     *     quantity_before: float,
     *     quantity_after: float,
     *     unit_price: float|null,
     *     inferred: true,
     * }
     */
    private function movement(
        PortfolioSnapshot $snapshot,
        PositionInferredMovementSide $side,
        float $quantity,
        float $quantityBefore,
        float $quantityAfter,
        ?float $unitPrice,
        CarbonImmutable $importedAt,
    ): array {
        return [
            'snapshot_id' => $snapshot->id,
            'imported_at' => $importedAt->toIso8601String(),
            'side' => $side->value,
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'unit_price' => $unitPrice,
            'inferred' => true,
        ];
    }

    private function resolveImportedAt(mixed $importedAt): CarbonImmutable
    {
        if ($importedAt instanceof CarbonImmutable) {
            return $importedAt;
        }

        return CarbonImmutable::parse((string) $importedAt);
    }
}
