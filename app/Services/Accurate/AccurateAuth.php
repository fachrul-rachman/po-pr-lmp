<?php

namespace App\Services\Accurate;

use DateTimeInterface;

final class AccurateAuth
{
    public function buildHeaders(?DateTimeInterface $now = null): array
    {
        $token = (string) config('accurate.api_token');
        $secret = (string) config('accurate.signature_secret');

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
}
