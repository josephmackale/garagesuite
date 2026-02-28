<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentConfig;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MpesaStkService
{
    public function initiateStkPush(PaymentConfig $config, string $phone, float $amount, ?int $invoiceId): array
    {
        // Normalize phone (you can improve this for KE numbers)
        $msisdn = preg_replace('/\D+/', '', $phone);

        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($config->shortcode . $config->passkey . $timestamp);

        $callbackUrl = $config->callback_url; // should point to your API callback route

        // Create a pending payment (upsert-ish)
        $payment = Payment::updateOrCreate(
            [
                'invoice_id' => $invoiceId,
                'status'     => 'pending',
                'provider'   => $config->provider ?? 'mpesa',
            ],
            [
                'phone'    => $msisdn,
                'amount'   => $amount,
                'currency' => 'KES',
                'meta'     => ['initiated_at' => now()->toISOString()],
            ]
        );


        PaymentLog::create([
            'provider' => $config->provider ?? 'mpesa',
            'direction'=> 'request',
            'event'    => 'stk_push',
            'invoice_id' => $invoiceId,
            'payment_id' => $payment->id,
            'phone'      => $msisdn,
            'amount'     => $amount,
            'payload'    => [
                'note' => 'Preparing STK request',
            ],
        ]);

        $token = $this->getAccessToken($config);

        $baseUrl = $config->environment === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $payload = [
            'BusinessShortCode' => $config->shortcode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => 'CustomerPayBillOnline',
            'Amount'            => (int)round($amount),
            'PartyA'            => $msisdn,
            'PartyB'            => $config->shortcode,
            'PhoneNumber'       => $msisdn,
            'CallBackURL'       => $callbackUrl,
            'AccountReference'  => $config->account_reference ?? ('INV-' . ($invoiceId ?? Str::random(6))),
            'TransactionDesc'   => $config->transaction_desc ?? 'Invoice Payment',
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($baseUrl . '/mpesa/stkpush/v1/processrequest', $payload);

        $body = $response->json() ?? [];

        PaymentLog::create([
            'provider' => $config->provider ?? 'mpesa',
            'direction'=> 'response',
            'event'    => 'stk_push',
            'invoice_id' => $invoiceId,
            'payment_id' => $payment->id,
            'phone'      => $msisdn,
            'amount'     => $amount,
            'merchant_request_id' => $body['MerchantRequestID'] ?? null,
            'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
            'result_code'         => $body['ResponseCode'] ?? null,
            'result_desc'         => $body['ResponseDescription'] ?? ($body['errorMessage'] ?? null),
            'payload'             => $body,
        ]);

        // Store request IDs on payment for later correlation
        $payment->update([
            'merchant_request_id' => $body['MerchantRequestID'] ?? null,
            'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
            'result_code'         => $body['ResponseCode'] ?? null,
            'result_desc'         => $body['ResponseDescription'] ?? ($body['errorMessage'] ?? null),
            'meta' => array_merge($payment->meta ?? [], [
                'stk_request_payload' => $payload,
            ]),
        ]);

        return $body;
    }

    private function getAccessToken(PaymentConfig $config): string
    {
        $baseUrl = $config->environment === 'live'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $resp = Http::withBasicAuth($config->consumer_key, $config->consumer_secret)
            ->acceptJson()
            ->get($baseUrl . '/oauth/v1/generate?grant_type=client_credentials');

        $json = $resp->json();

        if (!is_array($json) || empty($json['access_token'])) {
            throw new \RuntimeException('Failed to obtain M-PESA access token');
        }

        return (string)$json['access_token'];
    }
}
