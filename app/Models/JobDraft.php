<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class JobDraft extends Model
{
    protected $table = 'job_drafts';

    protected $fillable = [
        'garage_id','user_id','draft_uuid',
        'customer_id','vehicle_id',
        'payer_type','payer','details',
        'last_step','status','job_id',
    ];

    protected $casts = [
        'payer'   => 'array',
        'details' => 'array',
    ];

    /**
     * Photos/files attached to this draft (polymorphic).
     * Requires media_attachments to have: attachable_type, attachable_id.
     */
    public function mediaAttachments(): MorphMany
    {
        return $this->morphMany(\App\Models\MediaAttachment::class, 'attachable')
            ->latest();
    }
}
