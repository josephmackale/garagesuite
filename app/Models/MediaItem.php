<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaItem extends Model
{
    protected $table = 'media_items';

    protected $fillable = [
        'garage_id',
        'media_uuid',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'content_hash', // ✅ ADD THIS
    ];
    /**
     * ✅ Enables `$item->url` in Blade/JSON
     */
    protected $appends = ['url'];

    /**
     * Optional: cast numeric fields for safety
     */
    protected $casts = [
        'garage_id'   => 'int',
        'size_bytes'  => 'int',
        'width'       => 'int',
        'height'      => 'int',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    /**
     * Attachments pivot rows for this media item.
     */
    public function attachments()
    {
        return $this->hasMany(MediaAttachment::class, 'media_item_id');
    }

    /**
     * ✅ Method form (useful in PHP code)
     */
    public function url(): string
    {
        $disk = $this->disk ?: 'public';
        $path = $this->path ?: '';

        if ($path === '') {
            return '';
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * ✅ Accessor form: `$item->url`
     */
    public function getUrlAttribute(): string
    {
        return $this->url();
    }
}
