<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $table = 'sms_logs';

    protected $fillable = [
        'garage_id',
        'sms_message_id',
        'provider',
        'status',     // success|failed|queued|sent etc.
        'response',   // raw provider response (json/text)
        'error',      // error message if any
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Helper: always return response as array when it is JSON,
     * otherwise return ['raw' => <string>].
     */
    public function responseArray(): array
    {
        $r = $this->response;

        if (is_array($r)) {
            return $r;
        }

        if (is_string($r) && $r !== '') {
            $decoded = json_decode($r, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }

            return ['raw' => $r];
        }

        return [];
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SmsMessage::class, 'sms_message_id');
    }
}
