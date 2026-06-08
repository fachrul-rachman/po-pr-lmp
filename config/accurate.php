<?php

return [
    // Backward-compatible single-company keys (used when company is not specified).
    'api_token' => env('ACCURATE_API_TOKEN'),
    'signature_secret' => env('ACCURATE_SIGNATURE_SECRET'),
    'default_host' => env('ACCURATE_DEFAULT_HOST'),

    // Multi-company credentials. When specified, callers must pick a company key (e.g. kpus/ahl).
    'companies' => [
        'kpus' => [
            'api_token' => env('ACCURATE_KPUS_API_TOKEN', env('ACCURATE_API_TOKEN')),
            'signature_secret' => env('ACCURATE_KPUS_SIGNATURE_SECRET', env('ACCURATE_SIGNATURE_SECRET')),
            'default_host' => env('ACCURATE_KPUS_DEFAULT_HOST', env('ACCURATE_DEFAULT_HOST')),
        ],
        'ahl' => [
            'api_token' => env('ACCURATE_AHL_API_TOKEN'),
            'signature_secret' => env('ACCURATE_AHL_SIGNATURE_SECRET'),
            'default_host' => env('ACCURATE_AHL_DEFAULT_HOST'),
        ],
    ],

    // Host can change; the service will cache the discovered host.
    'host_cache_ttl_days' => (int) env('ACCURATE_HOST_CACHE_TTL_DAYS', 30),

    // Safety timeout for external calls.
    'timeout_seconds' => (int) env('ACCURATE_TIMEOUT_SECONDS', 20),
];
