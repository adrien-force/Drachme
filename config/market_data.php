<?php

declare(strict_types=1);

return [
  'enabled' => (bool) env('MARKET_DATA_ENABLED', true),
  'cache_ttl' => (int) env('MARKET_DATA_CACHE_TTL', 3600),
  'history_limit' => (int) env('MARKET_DATA_HISTORY_LIMIT', 100),
  'openfigi' => [
    'base_url' => 'https://api.openfigi.com/v3',
    'api_key' => env('OPENFIGI_API_KEY'),
  ],
  'yahoo' => [
    'chart_base_url' => 'https://query1.finance.yahoo.com/v8/finance/chart',
    'user_agent' => env(
      'YAHOO_FINANCE_USER_AGENT',
      'Mozilla/5.0 (compatible; Drachme/1.0; +https://github.com/drachme)',
    ),
  ],
];
