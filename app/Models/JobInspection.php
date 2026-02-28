<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobInspection extends Model
{
    protected $fillable = [
        'garage_id','job_id','draft_uuid','type','status','completed_at','completed_by'
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(JobInspectionItem::class, 'inspection_id');
    }

    // If your MediaAttachment is morphable:
    public function mediaAttachments()
    {
        return $this->morphMany(MediaAttachment::class, 'attachable');
    }

    public function mediaItems()
    {
        return $this->morphToMany(
            \App\Models\MediaItem::class,
            'attachable',
            'media_attachments',
            'attachable_id',
            'media_item_id'
        )->orderBy('media_attachments.id', 'desc');
    }

}
