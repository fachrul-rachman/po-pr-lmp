<?php

namespace App\Services\Accurate;

use DateTimeInterface;

final class AccurateAuth
{
    public function buildHeaders(?DateTimeInterface $now = null, ?string $company = null): array
    {
        [$token, $secret] = $this->resolveCredentials($company);

        if ($token === '' || $secret === '') {
            throw AccurateException::integration('Accurate credentials are not configured.');
        }

        $timestamp = ($now ?? now())->format(DATE_ATOM);
        $signature = $this->signatureForTimestamp($timestamp, $secret);

        return [
            'Authorization' => "Bearer {$token}",
            'X-Api-Timestamp' => $timestamp,
            'X-Api-Signature' => $signature,
        ];
    }

    public function signatureForTimestamp(string $timestamp, string $secret): string
    {
        $raw = hash_hmac('sha256', $timestamp, $secret, true);
        return base64_encode($raw);
    }

    /**
     * @return array{0:string,1:string} [api_token, signature_secret]
     */
    private function resolveCredentials(?string $company): array
    {
        $company = is_string($company) ? trim(strtolower($company)) : null;

        if ($company) {
            $token = (string) config("accurate.companies.{$company}.api_token");
            $secret = (string) config("accurate.companies.{$company}.signature_secret");

            return [$token, $secret];
        }

        return [
            (string) config('accurate.api_token'),
            (string) config('accurate.signature_secret'),
        ];
    }
}
