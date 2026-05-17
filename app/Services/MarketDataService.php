<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\MarketPriceRefreshResult;
use App\Enums\AccountType;
use App\Exceptions\MarketDataQuotaExceededException;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;
use App\Services\MarketData\AlphaVantageClient;
use App\Services\MarketData\IsinSymbolResolver;
use App\Support\Isin;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MarketDataService
{
    public function __construct(
        private readonly AlphaVantageClient $client,
        private readonly IsinSymbolResolver $symbols,
        private readonly NetWorthSnapshotService $netWorthSnapshots,
    ) {}

    /**
     * Returns a unit price string (6 decimals) or null when unavailable.
     */
    public function fetchPriceForIsin(string $isin, ?string $label = null): ?string
    {
        $symbol = $this->symbols->resolve($isin, $label);

        if ($symbol === null) {
            return null;
        }

        $cacheKey = 'market_price:'.Isin::normalize($isin).':'.strtoupper($symbol);
        $ttl = (int) config('alpha_vantage.cache_ttl', 3600);

        /** @var string|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $body = $this->client->globalQuote($symbol);
        $quote = $body['Global Quote'] ?? null;

        if (! is_array($quote)) {
            return null;
        }

        $priceRaw = $quote['05. price'] ?? null;

        if (! is_string($priceRaw) && ! is_numeric($priceRaw)) {
            return null;
        }

        $price = number_format((float) $priceRaw, 6, '.', '');

        Cache::put($cacheKey, $price, $ttl);

        return $price;
    }

    public function refreshForUser(User $user): MarketPriceRefreshResult
    {
        if (! is_string(config('alpha_vantage.api_key')) || config('alpha_vantage.api_key') === '') {
            throw new InvalidArgumentException('market_data_api_key_missing');
        }

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
                $price = $this->fetchPriceForIsin($position->isin, $position->label);

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
                Log::channel('market_data')->warning('alpha_vantage_quota', [
                    'user_id' => $user->id,
                    'isin' => $position->isin,
                    'message' => $quotaMessage,
                ]);

                break;
            } catch (\Throwable $exception) {
                $failed++;
                Log::channel('market_data')->warning('alpha_vantage_position_failed', [
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
}
