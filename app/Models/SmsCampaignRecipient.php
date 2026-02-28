<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'customer_id',
        'job_id',
        'phone',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
