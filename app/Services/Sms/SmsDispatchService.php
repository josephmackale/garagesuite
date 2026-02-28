<?php

namespace App\Services\Sms;

use App\Models\SmsLog;
use App\Models\SmsMessage;
use App\Services\SmsSender;

class SmsDispatchService
{
    public function __construct(private SmsSender $sender) {}

    public function sendOne(SmsMessage $sms): SmsMessage
    {
        // Only send if queued or failed (or empty)
        if (!in_array($sms->status, ['queued', 'failed', null, ''], true)) {
            return $sms;
        }

        $sms->update(['status' => 'sending']);

        $result = $this->sender->sendWithResult(
            phone: $sms->phone,
            message: $sms->message,
            meta: [
                'sms_message_id' => $sms->id,
                'garage_id' => $sms->garage_id,
            ]
        );

        SmsLog::create([
            'garage_id' => $sms->garage_id,
            'sms_message_id' => $sms->id,
            'provider' => $result['provider'] ?? config('sms.driver', 'unknown'),
            'status' => ($result['ok'] ?? false) ? 'success' : 'failed',
            'response' => is_string($result['response'] ?? null)
                ? ($result['response'] ?? null)
                : json_encode($result['response'] ?? null),
            'error' => $result['error'] ?? null,
            'sent_at' => now(),
        ]);

        if (($result['ok'] ?? false) === true) {
            $sms->update([
                'status' => 'sent',
                'provider' => $result['provider'] ?? config('sms.driver', 'unknown'),
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'error_message' => null,
                'sent_at' => now(),
            ]);
        } else {
            $sms->update([
                'status' => 'failed',
                'provider' => $result['provider'] ?? config('sms.driver', 'unknown'),
                'provider_message_id' => $result['provider_message_id'] ?? null,
                'error_message' => $result['error'] ?? 'Send failed',
            ]);
        }

        return $sms->fresh();
    }
}
