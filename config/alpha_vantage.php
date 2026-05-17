<?php

declare(strict_types=1);

return [
    'api_key' => env('ALPHA_VANTAGE_API_KEY'),
    'cache_ttl' => (int) env('ALPHA_VANTAGE_CACHE_TTL', 3600),
    'base_url' => 'https://www.alphavantage.co/query',
];
