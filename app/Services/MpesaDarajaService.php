<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MpesaDarajaService
{
    /**
     * Normalizes phone to 2547XXXXXXXX format.
     */
    public static function normalizePhoneTo254(string $phone): string
    {
        $p = preg_replace('/\D+/', '', $phone);

        if ($p === '') {
            throw new RuntimeException('Phone number is empty.');
        }

        if (str_starts_with($p, '0')) {
            $p = '254' . substr($p, 1); // 07XXXXXXXX -> 2547XXXXXXXX
        } elseif (str_starts_with($p, '7')) {
            $p = '254' . $p; // 7XXXXXXXX -> 2547XXXXXXXX
        } elseif (str_starts_with($p, '254')) {
            // ok
        } else {
            throw new RuntimeException('Invalid phone format. Use 07XXXXXXXX or 2547XXXXXXXX.');
        }

        if (!preg_match('/^2547\d{8}$/', $p)) {
            throw new RuntimeException('Invalid phone after normalization. Expected 2547XXXXXXXX.');
        }

        return $p;
    }

    protected function baseUrl(): string
    {
        $env = strtolower((string) config('services.mpesa.env', env('MPESA_ENV', 'sandbox')));

        return $env === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    protected function consumerKey(): string
    {
        return (string) config('services.mpesa.consumer_key', env('MPESA_CONSUMER_KEY', ''));
    }

    protected function consumerSecret(): string
    {
        return (string) config('services.mpesa.consumer_secret', env('MPESA_CONSUMER_SECRET', ''));
    }

    /**
     * Access token (cached).
     */
    public function getAccessToken(): string
    {
        $key = trim($this->consumerKey());
        $secret = trim($this->consumerSecret());

        if ($key === '' || $secret === '') {
            throw new RuntimeException('MPESA credentials missing: set MPESA_CONSUMER_KEY and MPESA_CONSUMER_SECRET.');
        }

        $cacheKey = 'mpesa_access_token_' . md5($this->baseUrl() . '|' . $key);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($key, $secret) {
            $url = $this->baseUrl() . '/oauth/v1/generate';

            $resp = Http::withBasicAuth($key, $secret)
                ->acceptJson()
                ->get($url, ['grant_type' => 'client_credentials']);

            if (!$resp->successful()) {
                throw new RuntimeException("Daraja token request failed ({$resp->status()}): " . $resp->body());
            }

            $token = $resp->json('access_token');

            if (!$token) {
                throw new RuntimeException('Daraja token response missing access_token: ' . $resp->body());
            }

            return $token;
        });
    }

    /**
     * Build payload for STK Push.
     */
    public function buildStkPayload(string $phone254, int $amount, string $accountRef, string $desc): array
    {
        $shortcode = (string) config('services.mpesa.shortcode', env('MPESA_SHORTCODE', ''));
        $passkey   = (string) config('services.mpesa.passkey', env('MPESA_PASSKEY', ''));
        $callback  = (string) config('services.mpesa.callback_url', env('MPESA_CALLBACK_URL', ''));

        if ($shortcode === '' || $passkey === '' || $callback === '') {
            throw new RuntimeException('MPESA config missing: MPESA_SHORTCODE / MPESA_PASSKEY / MPESA_CALLBACK_URL.');
        }

        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($shortcode . $passkey . $timestamp);

        return [
            'BusinessShortCode' => $shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone254,
            'PartyB'            => $shortcode,
            'PhoneNumber'       => $phone254,
            'CallBackURL'       => $callback,
            'AccountReference'  => $accountRef,
            'TransactionDesc'   => $desc,
        ];
    }

    /**
     * Perform STK push.
     */
    public function stkPush(array $payload): array
    {
        $token = $this->getAccessToken();

        $url = $this->baseUrl() . '/mpesa/stkpush/v1/processrequest';

        $resp = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($url, $payload);

        if (!$resp->successful()) {
            throw new RuntimeException("Daraja STK request failed ({$resp->status()}): " . $resp->body());
        }

        return (array) $resp->json();
    }
}
