<?php

namespace App\Services\Sms\Gateways;

use Illuminate\Support\Facades\Http;

class HostPinnacleSender
{
    public function sendQuick(string $to, string $message, ?string $senderId = null): array
    {
        $cfg = config('sms.drivers.hostpinnacle');

        $baseUrl = rtrim($cfg['base_url'], '/');
        $sendUrl = $baseUrl . $cfg['send_path'];

        // HostPinnacle docs: sendMethod=quick, mobile, msg, senderid, msgType, output. :contentReference[oaicite:2]{index=2}
        $payload = [
            'sendMethod' => $cfg['send_method'] ?? 'quick',
            'mobile'     => $to,
            'msg'        => $message,
            'msgType'    => $cfg['msg_type'] ?? 'text',
            'output'     => $cfg['output'] ?? 'json',
        ];

        // “Sender ID optional” support: omit if null.
        if (!empty($senderId)) {
            $payload['senderid'] = $senderId;
        }

        // Auth: either apiKey header OR userId/password body. :contentReference[oaicite:3]{index=3}
        $headers = ['content-type' => 'application/x-www-form-urlencoded'];
        if (!empty($cfg['api_key'])) {
            $headers['apikey'] = $cfg['api_key'];
        } else {
            $payload['userid'] = $cfg['user_id'];
            $payload['password'] = $cfg['password'];
        }

        $resp = Http::asForm()
            ->withHeaders($headers)
            ->timeout((int)($cfg['timeout'] ?? 30))
            ->post($sendUrl, $payload);

        $json = null;
        try {
            $json = $resp->json();
        } catch (\Throwable $e) {
            // keep null; we’ll store raw body too
        }

        return [
            'ok' => $resp->successful(),
            'http_status' => $resp->status(),
            'endpoint' => $sendUrl,
            'request' => $payload,
            'response_raw' => $resp->body(),
            'response_json' => $json,
        ];
    }
}
