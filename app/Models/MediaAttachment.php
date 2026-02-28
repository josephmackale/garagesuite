<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAttachment extends Model
{
    protected $fillable = [
        'garage_id','media_item_id','attachable_type','attachable_id','label'
    ];

    public function mediaItem()
    {
        return $this->belongsTo(MediaItem::class);
    }

    public function attachable()
    {
        return $this->morphTo();
    }
}
