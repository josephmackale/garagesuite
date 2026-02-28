<?php

namespace App\Jobs;

use App\Models\SmsMessage;
use App\Services\Sms\SmsDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSmsMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $smsMessageId) {}

    public function handle(SmsDispatchService $svc): void
    {
        $msg = SmsMessage::query()->findOrFail($this->smsMessageId);

        // Avoid double-send
        if (!in_array($msg->status, ['queued', 'failed'], true)) {
            return;
        }

        $svc->sendSmsMessage($msg);
    }
}
