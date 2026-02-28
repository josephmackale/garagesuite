<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsMessage extends Model
{
    // Optional: standardize statuses you will use
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    protected $table = 'sms_messages';

    protected $fillable = [
        'garage_id',
        'sms_campaign_id',
        'customer_id',
        'phone',
        'message',
        'status',
        'provider',
        'provider_message_id',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * Small helper: normalize phone numbers before sending if needed.
     * (You can expand later; for now we just trim spaces.)
     */
    public function getPhoneForSending(): string
    {
        return trim((string) $this->phone);
    }

    /**
     * Small helper: whether this message can be sent now.
     */
    public function isSendable(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_FAILED, null, ''], true);
    }

    /**
     * ✅ Owning garage (multi-tenant)
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * ✅ Delivery logs for this SMS
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'sms_message_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'sms_campaign_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
