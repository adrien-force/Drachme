<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Exceptions\MarketDataQuotaExceededException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AlphaVantageClient
{
    /**
     * @return array<string, mixed>
     */
    public function globalQuote(string $symbol): array
    {
        return $this->request([
            'function' => 'GLOBAL_QUOTE',
            'symbol' => $symbol,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function symbolSearch(string $keywords): array
    {
        return $this->request([
            'function' => 'SYMBOL_SEARCH',
            'keywords' => $keywords,
        ]);
    }

    /**
     * @param  array<string, string>  $params
     * @return array<string, mixed>
     */
    private function request(array $params): array
    {
        $apiKey = config('alpha_vantage.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new InvalidArgumentException('market_data_api_key_missing');
        }

        $query = array_merge($params, ['apikey' => $apiKey]);

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get((string) config('alpha_vantage.base_url'), $query);
        } catch (ConnectionException $exception) {
            Log::channel('market_data')->warning('alpha_vantage_connection_failed', [
                'params' => $params,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        Log::channel('market_data')->info('alpha_vantage_request', [
            'function' => $params['function'] ?? null,
            'symbol' => $params['symbol'] ?? null,
            'keywords' => $params['keywords'] ?? null,
            'status' => $response->status(),
        ]);

        if (isset($body['Note']) || isset($body['Information'])) {
            $message = (string) ($body['Note'] ?? $body['Information']);

            throw new MarketDataQuotaExceededException($message);
        }

        return $body;
    }
}
