<?php

namespace App\Services\Accurate;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class AccurateHttpClient
{
    private const REDIRECT_STATUSES = [301, 302, 303, 307, 308];

    public function __construct(
        private AccurateAuth $auth,
    ) {}

    public function get(string $url, array $query = [], ?string $company = null): array
    {
        $res = $this->requestWithManualRedirects('GET', $url, [
            'query' => $query,
        ], $company);

        return $this->decodeJson($res);
    }

    public function post(string $url, array $data = [], ?string $company = null): array
    {
        $res = $this->requestWithManualRedirects('POST', $url, [
            'json' => $data,
        ], $company);

        return $this->decodeJson($res);
    }

    private function baseRequest(?string $company = null): PendingRequest
    {
        return Http::withHeaders($this->auth->buildHeaders(company: $company))
            ->timeout((int) config('accurate.timeout_seconds', 20))
            // Redirects are handled manually to preserve auth headers across hosts.
            ->withOptions(['allow_redirects' => false]);
    }

    private function requestWithManualRedirects(string $method, string $url, array $options, ?string $company = null): Response
    {
        $max = 5;
        $currentUrl = $url;
        $currentMethod = $method;
        $currentOptions = $options;

        for ($i = 0; $i <= $max; $i++) {
            $req = $this->baseRequest($company);

            $res = $req->send($currentMethod, $currentUrl, $currentOptions);

            if (! in_array($res->status(), self::REDIRECT_STATUSES, true)) {
                return $res;
            }

            $location = $res->header('Location');
            if (! is_string($location) || $location === '') {
                throw AccurateException::invalidResponse('Accurate returned redirect without Location header.');
            }

            $currentUrl = $this->resolveRedirectUrl($currentUrl, $location);

            // RFC 7231: 303 means "See Other" and should be followed with GET.
            if ($res->status() === 303) {
                $currentMethod = 'GET';
                unset($currentOptions['json']);
            }
        }

        throw AccurateException::integration('Accurate redirect limit exceeded.');
    }

    private function resolveRedirectUrl(string $currentUrl, string $location): string
    {
        // Absolute URL.
        if (preg_match('/^https?:\\/\\//i', $location) === 1) {
            return $location;
        }

        $parts = parse_url($currentUrl);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        if (! is_string($scheme) || $scheme === '' || ! is_string($host) || $host === '') {
            // Fallback: return as-is and let the request fail with a clearer error.
            return $location;
        }

        $base = $scheme.'://'.$host.$port;

        // Root-relative path.
        if (str_starts_with($location, '/')) {
            return $base.$location;
        }

        // Path-relative.
        $path = (string) ($parts['path'] ?? '/');
        $dir = rtrim(str_replace('\\', '/', dirname($path)), '/');
        if ($dir === '') {
            $dir = '/';
        }

        return rtrim($base.$dir, '/').'/'.$location;
    }

    private function decodeJson(Response $res): array
    {
        if (! $res->successful()) {
            throw AccurateException::integration('Accurate request failed with HTTP '.$res->status().'.');
        }

        $json = $res->json();
        if (! is_array($json)) {
            throw AccurateException::invalidResponse('Accurate response is not valid JSON object.');
        }

        if (($json['s'] ?? null) !== true) {
            $msg = is_string($json['d']['message'] ?? null) ? $json['d']['message'] : 'Accurate returned s=false.';
            throw AccurateException::integration($msg);
        }

        return $json;
    }
}
