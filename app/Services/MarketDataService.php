<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\MarketPriceRefreshResult;
use App\Enums\AccountType;
use App\Exceptions\MarketDataQuotaExceededException;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use App\Services\MarketData\IsinSymbolResolver;
use App\Services\MarketData\YahooFinanceClient;
use App\Support\Isin;
use App\Support\MarketDataLogger;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class MarketDataService
{
    public function __construct(
        private readonly YahooFinanceClient $client,
        private readonly IsinSymbolResolver $symbols,
        private readonly NetWorthSnapshotService $netWorthSnapshots,
    ) {}

    /**
     * Returns a unit price string (6 decimals) or null when unavailable.
     */
    public function fetchPriceForIsin(string $isin, ?string $label = null, ?string $marketSymbol = null): ?string
    {
        $symbol = $this->symbols->resolve($isin, $label, $marketSymbol);

        if ($symbol === null) {
            return null;
        }

        $cacheKey = 'market_price:'.Isin::normalize($isin).':'.strtoupper($symbol);
        $ttl = $this->cacheTtl();

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $price = $this->client->quotePrice($symbol);

        if ($price === null) {
            return null;
        }

        Cache::put($cacheKey, $price, $ttl);

        return $price;
    }

    /**
     * @return list<array{ date: string, price: float }>
     */
    public function cachedDailyHistoryForPosition(Position $position): array
    {
        $symbol = $this->symbols->resolve(
            $position->isin,
            $position->label,
            $position->market_symbol,
        );

        if ($symbol === null) {
            return [];
        }

        $cacheKey = $this->historyCacheKey($position->isin, $symbol);
        $cached = Cache::get($cacheKey);

        if (! is_array($cached)) {
            return [];
        }

        /** @var list<array{ date: string, price: float }> $cached */
        return $cached;
    }

    /**
     * @return list<array{ date: string, price: float }>
     */
    public function refreshDailyHistoryForPosition(Position $position): array
    {
        $this->assertMarketDataEnabled();

        $symbol = $this->symbols->resolve(
            $position->isin,
            $position->label,
            $position->market_symbol,
        );

        if ($symbol === null) {
            return [];
        }

        $points = $this->client->historicalDailyClose($symbol);

        Cache::put($this->historyCacheKey($position->isin, $symbol), $points, $this->cacheTtl());

        return $points;
    }

    /**
     * @return 'updated'|'skipped'
     */
    public function refreshPosition(Position $position): string
    {
        $this->assertMarketDataEnabled();

        try {
            $price = $this->fetchPriceForIsin(
                $position->isin,
                $position->label,
                $position->market_symbol,
            );

            if ($price === null) {
                return 'skipped';
            }

            $position->update([
                'last_price' => $price,
                'last_price_at' => now(),
            ]);

            $user = $position->user;

            if ($user !== null) {
                $this->netWorthSnapshots->recordForUser($user);
            }

            return 'updated';
        } catch (MarketDataQuotaExceededException $exception) {
            MarketDataLogger::warning('market_data_quota', [
                'user_id' => $position->user_id,
                'isin' => $position->isin,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function refreshForUser(User $user): MarketPriceRefreshResult
    {
        $this->assertMarketDataEnabled();

        $accountIds = Account::query()
            ->where('user_id', $user->id)
            ->where('type', AccountType::Invest)
            ->where('is_archived', false)
            ->pluck('id');

        $positions = Position::query()
            ->where('user_id', $user->id)
            ->whereIn('account_id', $accountIds)
            ->get();

        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $quotaMessage = null;

        foreach ($positions as $position) {
            try {
                $price = $this->fetchPriceForIsin(
                    $position->isin,
                    $position->label,
                    $position->market_symbol,
                );

                if ($price === null) {
                    $skipped++;
                    continue;
                }

                $position->update([
                    'last_price' => $price,
                    'last_price_at' => now(),
                ]);
                $updated++;
            } catch (MarketDataQuotaExceededException $exception) {
                $quotaMessage = $exception->getMessage();
                MarketDataLogger::warning('market_data_quota', [
                    'user_id' => $user->id,
                    'isin' => $position->isin,
                    'message' => $quotaMessage,
                ]);

                break;
            } catch (\Throwable $exception) {
                $failed++;
                MarketDataLogger::warning('market_data_position_failed', [
                    'user_id' => $user->id,
                    'isin' => $position->isin,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($updated > 0) {
            $this->netWorthSnapshots->recordForUser($user);
        }

        return new MarketPriceRefreshResult(
            updated: $updated,
            skipped: $skipped,
            failed: $failed,
            quotaMessage: $quotaMessage,
        );
    }

    public static function isConfigured(): bool
    {
        return (bool) config('market_data.enabled', true);
    }

    private function assertMarketDataEnabled(): void
    {
        if (! self::isConfigured()) {
            throw new InvalidArgumentException('market_data_disabled');
        }
    }

    private function cacheTtl(): int
    {
        return (int) config('market_data.cache_ttl', 3600);
    }

    private function historyCacheKey(string $isin, string $symbol): string
    {
        return 'market_history:'.Isin::normalize($isin).':'.strtoupper($symbol);
    }
}
