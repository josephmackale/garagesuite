<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsSender
{
    /**
     * Backward compatible: returns bool only.
     * Existing code can keep calling ->send(...)
     */
    public function send(string $phone, string $message, array $meta = []): bool
    {
        $res = $this->sendWithResult($phone, $message, $meta);
        return (bool) ($res['ok'] ?? false);
    }

    /**
     * New: returns structured result for Step 6 logging + sms_messages updates.
     *
     * Return shape:
     * [
     *   'ok' => bool,
     *   'provider' => string,
     *   'provider_message_id' => ?string,
     *   'error' => ?string,
     *   'response' => array|string|null,
     *   'http_status' => ?int,
     * ]
     */
    public function sendWithResult(string $phone, string $message, array $meta = []): array
    {
        $driver = config('sms.driver', 'fake');

        switch ($driver) {
            case 'fake':
                return $this->sendFake($phone, $message, $meta);

            case 'hostpinnacle':
                return $this->sendViaHostPinnacle($phone, $message, $meta);

            case 'africastalking':
                return $this->sendViaAfricasTalking($phone, $message, $meta);

            case 'twilio':
                return $this->sendViaTwilio($phone, $message, $meta);

            default:
                Log::warning('SMS driver not supported', [
                    'driver'  => $driver,
                    'to'      => $phone,
                    'message' => $message,
                    'meta'    => $meta,
                ]);

                return [
                    'ok' => false,
                    'provider' => (string) $driver,
                    'provider_message_id' => null,
                    'error' => 'SMS driver not supported: ' . $driver,
                    'response' => null,
                    'http_status' => null,
                ];
        }
    }

    /**
     * Driver: fake – just log, used for testing.
     */
    protected function sendFake(string $phone, string $message, array $meta = []): array
    {
        Log::info('SMS SENT (FAKE DRIVER)', [
            'to'      => $phone,
            'message' => $message,
            'meta'    => $meta,
        ]);

        return [
            'ok' => true,
            'provider' => 'fake',
            'provider_message_id' => 'fake-' . uniqid(),
            'error' => null,
            'response' => ['mode' => 'fake'],
            'http_status' => 200,
        ];
    }

    /**
     * Driver: HostPinnacle (REAL).
     * Uses your config/sms.php structure:
     *  sms.drivers.hostpinnacle.base_url
     *  sms.drivers.hostpinnacle.send_path
     *  sms.drivers.hostpinnacle.api_key
     *  sms.drivers.hostpinnacle.from (optional; if null we omit senderid)
     *  sms.drivers.hostpinnacle.timeout_seconds
     */
    protected function sendViaHostPinnacle(string $phone, string $message, array $meta = []): array
    {
        $cfg = config('sms.drivers.hostpinnacle', []);

        $baseUrl = rtrim((string)($cfg['base_url'] ?? ''), '/');
        $sendPath = (string)($cfg['send_path'] ?? '/SMSApi/send');
        $apiKey = (string)($cfg['api_key'] ?? '');
        $from = $cfg['from'] ?? null;
        $timeout = (int)($cfg['timeout_seconds'] ?? 15);

        if ($baseUrl === '' || $apiKey === '') {
            Log::error('HostPinnacle missing config', [
                'base_url' => $baseUrl,
                'has_api_key' => $apiKey !== '',
            ]);

            return [
                'ok' => false,
                'provider' => 'hostpinnacle',
                'provider_message_id' => null,
                'error' => 'HostPinnacle missing config: base_url or api_key',
                'response' => null,
                'http_status' => null,
            ];
        }

        $url = $baseUrl . $sendPath;

        // HostPinnacle docs: sendMethod=quick, mobile, msg, senderid, msgType, output=json
        $payload = [
            'sendMethod' => $cfg['send_method'] ?? 'quick',
            'mobile'     => $this->normalizeToPlusE164($phone),
            'msg'        => $message,
            'msgType'    => $cfg['msg_type'] ?? 'text',
            'output'     => $cfg['output'] ?? 'json',
        ];

        // Sender ID optional: only include if present
        if (!empty($from)) {
            $payload['senderid'] = $from;
        }

        try {
            $res = Http::asForm()
                ->timeout($timeout)
                ->withHeaders([
                    'apikey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post($url, $payload);

            $json = null;
            try {
                $json = $res->json();
            } catch (\Throwable $e) {
                $json = null;
            }

            // Many gateways return transactionId; we try to extract it safely.
            $providerMessageId = is_array($json) ? ($json['transactionId'] ?? null) : null;

            if (! $res->successful()) {
                return [
                    'ok' => false,
                    'provider' => 'hostpinnacle',
                    'provider_message_id' => $providerMessageId,
                    'error' => is_array($json) ? ($json['reason'] ?? 'Request failed') : $res->body(),
                    'response' => $json ?? $res->body(),
                    'http_status' => $res->status(),
                ];
            }

            // Some APIs return status=success/failed even when HTTP 200
            $status = is_array($json) ? strtolower((string)($json['status'] ?? '')) : '';
            if ($status !== '' && $status !== 'success') {
                return [
                    'ok' => false,
                    'provider' => 'hostpinnacle',
                    'provider_message_id' => $providerMessageId,
                    'error' => is_array($json) ? ($json['reason'] ?? 'Gateway status not success') : 'Gateway status not success',
                    'response' => $json ?? $res->body(),
                    'http_status' => $res->status(),
                ];
            }

            return [
                'ok' => true,
                'provider' => 'hostpinnacle',
                'provider_message_id' => $providerMessageId,
                'error' => null,
                'response' => $json ?? $res->body(),
                'http_status' => $res->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'provider' => 'hostpinnacle',
                'provider_message_id' => null,
                'error' => $e->getMessage(),
                'response' => null,
                'http_status' => null,
            ];
        }
    }

    /**
     * Driver: Africa's Talking (REAL).
     */
    protected function sendViaAfricasTalking(string $phone, string $message, array $meta = []): array
    {
        $cfg = config('sms.drivers.africastalking', []);
        $username = $cfg['username'] ?? null;
        $apiKey   = $cfg['api_key'] ?? null;
        $from     = $cfg['from'] ?? null;

        if (! $username || ! $apiKey) {
            return [
                'ok' => false,
                'provider' => 'africastalking',
                'provider_message_id' => null,
                'error' => 'AfricasTalking missing credentials',
                'response' => null,
                'http_status' => null,
            ];
        }

        $to = $this->normalizeToPlusE164($phone);

        $payload = [
            'username' => $username,
            'to'       => $to,
            'message'  => $message,
        ];

        if ($from) {
            $payload['from'] = $from;
        }

        try {
            $res = Http::asForm()
                ->timeout(20)
                ->withHeaders([
                    'apiKey' => $apiKey,
                    'Accept' => 'application/json',
                ])
                ->post('https://api.africastalking.com/version1/messaging', $payload);

            if (! $res->successful()) {
                return [
                    'ok' => false,
                    'provider' => 'africastalking',
                    'provider_message_id' => null,
                    'error' => $res->body(),
                    'response' => $res->body(),
                    'http_status' => $res->status(),
                ];
            }

            return [
                'ok' => true,
                'provider' => 'africastalking',
                'provider_message_id' => null,
                'error' => null,
                'response' => $res->json() ?? $res->body(),
                'http_status' => $res->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'provider' => 'africastalking',
                'provider_message_id' => null,
                'error' => $e->getMessage(),
                'response' => null,
                'http_status' => null,
            ];
        }
    }

    /**
     * Driver: Twilio (REAL).
     */
    protected function sendViaTwilio(string $phone, string $message, array $meta = []): array
    {
        $cfg = config('sms.drivers.twilio', []);
        $sid   = $cfg['sid'] ?? null;
        $token = $cfg['token'] ?? null;
        $from  = $cfg['from'] ?? null;

        if (! $sid || ! $token || ! $from) {
            return [
                'ok' => false,
                'provider' => 'twilio',
                'provider_message_id' => null,
                'error' => 'Twilio missing credentials',
                'response' => null,
                'http_status' => null,
            ];
        }

        $to = $this->normalizeToPlusE164($phone);

        try {
            $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

            $res = Http::asForm()
                ->timeout(20)
                ->withBasicAuth($sid, $token)
                ->post($url, [
                    'From' => $from,
                    'To'   => $to,
                    'Body' => $message,
                ]);

            $json = null;
            try { $json = $res->json(); } catch (\Throwable $e) {}

            $providerMessageId = is_array($json) ? ($json['sid'] ?? null) : null;

            if (! $res->successful()) {
                return [
                    'ok' => false,
                    'provider' => 'twilio',
                    'provider_message_id' => $providerMessageId,
                    'error' => $res->body(),
                    'response' => $json ?? $res->body(),
                    'http_status' => $res->status(),
                ];
            }

            return [
                'ok' => true,
                'provider' => 'twilio',
                'provider_message_id' => $providerMessageId,
                'error' => null,
                'response' => $json ?? $res->body(),
                'http_status' => $res->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'provider' => 'twilio',
                'provider_message_id' => null,
                'error' => $e->getMessage(),
                'response' => null,
                'http_status' => null,
            ];
        }
    }

    private function normalizeToPlusE164(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        // If user passed local 07... (Kenya), convert to 254...
        if (str_starts_with($digits, '0') && strlen($digits) >= 10) {
            $digits = '254' . substr($digits, 1);
        }

        return '+' . $digits;
    }
}
