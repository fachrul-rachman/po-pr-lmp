<?php

return [
    'api_token' => env('ACCURATE_API_TOKEN'),
    'signature_secret' => env('ACCURATE_SIGNATURE_SECRET'),
    'default_host' => env('ACCURATE_DEFAULT_HOST'),

    // Host can change; the service will cache the discovered host.
    'host_cache_ttl_days' => (int) env('ACCURATE_HOST_CACHE_TTL_DAYS', 30),

    // Safety timeout for external calls.
    'timeout_seconds' => (int) env('ACCURATE_TIMEOUT_SECONDS', 20),
];

