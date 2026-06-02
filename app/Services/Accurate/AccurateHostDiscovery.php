<?php

namespace App\Services\Accurate;

use Illuminate\Support\Facades\Cache;

final class AccurateHostDiscovery
{
    private const CACHE_KEY = 'accurate.host';

    public function __construct(
        private AccurateHttpClient $http,
    ) {}

    public function getHost(): string
    {
        $cached = Cache::get(self::CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $this->normalizeHost($cached);
        }

        $ttlDays = (int) config('accurate.host_cache_ttl_days', 30);

        try {
            $json = $this->http->post('https://account.accurate.id/api/api-token.do');
            $host = $json['d']['database']['host'] ?? null;

            if (! is_string($host) || $host === '') {
                throw AccurateException::invalidResponse('Accurate host discovery response missing d.database.host.');
            }

            $host = $this->normalizeHost($host);

            Cache::put(self::CACHE_KEY, $host, now()->addDays($ttlDays));

            return $host;
        } catch (\Throwable $e) {
            $fallback = (string) config('accurate.default_host');
            if ($fallback !== '') {
                return $this->normalizeHost($fallback);
            }

            throw $e;
        }
    }

    private function normalizeHost(string $host): string
    {
        $host = rtrim($host, '/');

        // Per spec: base URL must be {host}/accurate/api/...
        if (! str_ends_with(strtolower($host), '/accurate')) {
            $host .= '/accurate';
        }

        return $host;
    }
}
