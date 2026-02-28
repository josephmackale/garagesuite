<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaign extends Model
{
    protected $fillable = [
        'garage_id',
        'name',
        'message',
        'filters_json',
        'total_recipients',
        'sent_count',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'filters_json' => 'array',
        'completed_at' => 'datetime',
    ];

    public function garage()
    {
        return $this->belongsTo(Garage::class);
    }

    public function recipients()
    {
        return $this->hasMany(SmsCampaignRecipient::class, 'campaign_id');
    }
    public function messages()
    {
        return $this->hasMany(SmsMessage::class);
    }

}
